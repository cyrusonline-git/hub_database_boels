<?php

namespace App\Vraagbaak;

/** Alle vragen uit alle bronnen, met toegang per gebruiker. */
class Catalogus
{
    /** @return Vraag[] */
    public static function alle(): array
    {
        $uit = [];
        foreach ([Bronnen\Tankapp::class, Bronnen\Inhuur::class, Bronnen\Projectschade::class, Bronnen\Spoedverhuur::class, Bronnen\Voorraad::class, Bronnen\Scanner::class, Bronnen\Shell::class, Bronnen\Combinaties::class] as $klasse) {
            if (class_exists($klasse)) {
                foreach ($klasse::vragen() as $v) {
                    $uit[$v->id] = $v;
                }
            }
        }

        return $uit;
    }

    public static function vind(string $id): ?Vraag
    {
        return self::alle()[$id] ?? null;
    }

    /** Bronnen (slugs) die deze gebruiker mag raadplegen. */
    public static function toegestaneBronnen($user): array
    {
        if ($user->is_super_admin || $user->hasRole(['super-admin', 'administrator'])) {
            return array_keys((array) config('vraagbaak.bronnen'));
        }
        $slugs = collect($user->applications())->pluck('slug')->map(fn ($s) => (string) $s)->all();
        $uit = [];
        foreach ((array) config('vraagbaak.bronnen') as $bron => $cfg) {
            if (in_array($cfg['slug'], $slugs, true)) {
                $uit[] = $bron;
            }
        }

        return $uit;
    }

    public static function isManager($user): bool
    {
        if ($user->is_super_admin || $user->hasRole(['super-admin', 'administrator'])) {
            return true;
        }
        foreach ($user->roles ?? [] as $r) {
            if (str_contains((string) $r->slug, 'manager') || str_contains((string) $r->slug, 'admin') || str_contains((string) $r->slug, 'beheer') || str_contains((string) $r->slug, 'fleet')) {
                return true;
            }
        }

        return false;
    }

    /** Vragen die de gebruiker mag stellen (bron + rol), gegroepeerd per bron. */
    public static function voor($user): array
    {
        $bronnen = self::toegestaneBronnen($user);
        $manager = self::isManager($user);
        $uit = [];
        foreach (self::alle() as $v) {
            $bronnenNodig = explode('+', $v->bron);
            if (array_diff($bronnenNodig, $bronnen)) {
                continue;
            }
            if ($v->rol === 'manager' && ! $manager) {
                continue;
            }
            if ($v->rol === 'admin' && ! ($user->is_super_admin || $user->hasRole(['super-admin', 'administrator']))) {
                continue;
            }
            $uit[$v->bron][] = $v;
        }

        return $uit;
    }
}
