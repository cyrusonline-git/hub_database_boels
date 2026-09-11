<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Interne chat tussen Boels-medewerkers (alle actieve CORE-gebruikers).
 * Polling-gebaseerd — geen websockets nodig op shared hosting.
 */
class ChatController extends Controller
{
    /** Totaal ongelezen (voor het badge-bolletje) */
    public function unread(Request $request)
    {
        return [
            'count' => ChatMessage::where('recipient_id', $request->user()->id)
                ->whereNull('read_at')->count(),
        ];
    }

    /**
     * Pop-up-berichten (config boels.chat_popup): nieuwe, nog niet als pop-up
     * getoonde berichten van de ingestelde afzenders aan deze ontvanger.
     * Voor iedereen die niet in de ontvangerslijst staat: altijd leeg.
     */
    public function popup(Request $request)
    {
        $me = $request->user();
        $ontvangers = array_map('strtolower', config('boels.chat_popup.ontvangers', []));
        $afzenders = array_map('strtolower', config('boels.chat_popup.afzenders', []));
        if (! in_array(strtolower((string) $me->email), $ontvangers, true) || empty($afzenders)) {
            return ['messages' => []];
        }

        $afzenderIds = User::query()->whereIn(DB::raw('LOWER(email)'), $afzenders)->pluck('id')->all();
        if (empty($afzenderIds)) {
            return ['messages' => []];
        }

        $berichten = ChatMessage::with('sender')
            ->where('recipient_id', $me->id)
            ->whereIn('sender_id', $afzenderIds)
            ->whereNull('read_at')
            ->whereNull('popup_seen_at')
            ->orderBy('id')
            ->limit(10)
            ->get();

        return ['messages' => $berichten->map(fn ($m) => [
            'id' => $m->id,
            'from_id' => $m->sender_id,
            'from' => $m->sender?->name ?? 'Collega',
            'time' => $m->created_at?->format('d-m H:i'),
            'body' => $m->body,
            'image' => $m->image_path ? route('chat.image', $m) : null,
        ])->values()];
    }

    /** Pop-up weggeklikt: deze berichten niet nog eens als pop-up tonen. */
    public function popupSeen(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if ($ids) {
            ChatMessage::where('recipient_id', $request->user()->id)
                ->whereIn('id', $ids)
                ->whereNull('popup_seen_at')
                ->update(['popup_seen_at' => now()]);
        }
        return ['ok' => true];
    }

    /** Collega's: ongelezen eerst, dan recentste gesprek, dan alfabetisch */
    public function contacts(Request $request)
    {
        $me = $request->user()->id;

        $unread = ChatMessage::where('recipient_id', $me)->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as c')->groupBy('sender_id')->pluck('c', 'sender_id');

        $lastMessages = ChatMessage::where('sender_id', $me)->orWhere('recipient_id', $me)
            ->orderByDesc('id')->limit(300)->get()
            ->groupBy(fn ($m) => $m->sender_id === $me ? $m->recipient_id : $m->sender_id)
            ->map(fn ($msgs) => $msgs->first());

        $contacts = User::where('id', '!=', $me)
            ->where('active', true)->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($u) use ($unread, $lastMessages, $me) {
                $last = $lastMessages[$u->id] ?? null;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'initials' => collect(explode(' ', trim($u->name)))
                        ->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode(''),
                    'unread' => (int) ($unread[$u->id] ?? 0),
                    'last_body' => $last ? (trim($last->body) !== '' ? mb_substr($last->body, 0, 40) : '📷 Foto') : null,
                    'last_mine' => $last ? $last->sender_id === $me : false,
                    'last_at' => $last?->created_at?->format('d-m H:i'),
                    'last_id' => $last?->id ?? 0,
                ];
            })
            ->sortBy([['unread', 'desc'], ['last_id', 'desc'], ['name', 'asc']])
            ->values();

        return ['contacts' => $contacts];
    }

    /** Gesprek met één collega; inkomende berichten worden als gelezen gemarkeerd */
    public function thread(Request $request, User $user)
    {
        $me = $request->user()->id;

        ChatMessage::where('sender_id', $user->id)->where('recipient_id', $me)
            ->whereNull('read_at')->update(['read_at' => now()]);

        $messages = ChatMessage::where(fn ($q) => $q
                ->where(fn ($a) => $a->where('sender_id', $me)->where('recipient_id', $user->id))
                ->orWhere(fn ($b) => $b->where('sender_id', $user->id)->where('recipient_id', $me)))
            ->orderByDesc('id')->limit(60)->get()->reverse()->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'mine' => $m->sender_id === $me,
                'body' => $m->body,
                'image' => $m->image_path ? route('chat.image', $m->id) : null,
                'time' => $m->created_at->format('d-m H:i'),
                'read' => $m->read_at !== null,
            ]);

        return ['with' => ['id' => $user->id, 'name' => $user->name], 'messages' => $messages];
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id', 'not_in:'.$request->user()->id],
            'body' => ['nullable', 'required_without:image', 'string', 'max:2000'],
            'image' => ['nullable', 'required_without:body', 'image', 'mimes:jpeg,png,gif,webp', 'max:8192'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Privé-opslag (niet publiek benaderbaar); uitgeserveerd via chat.image
            $imagePath = $request->file('image')->store('chat', 'local');
        }

        $message = ChatMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $data['recipient_id'],
            'body' => trim($data['body'] ?? ''),
            'image_path' => $imagePath,
        ]);

        return ['ok' => true, 'id' => $message->id];
    }

    /** Meegestuurde foto tonen — alleen voor de twee gespreksdeelnemers */
    public function image(Request $request, ChatMessage $message)
    {
        $me = $request->user()->id;
        abort_unless(in_array($me, [$message->sender_id, $message->recipient_id], true), 403);
        abort_unless($message->image_path && Storage::disk('local')->exists($message->image_path), 404);

        return Storage::disk('local')->response($message->image_path);
    }

    /** Eigen bericht verwijderen (voor beide kanten weg, incl. eventuele foto) */
    public function destroy(Request $request, ChatMessage $message)
    {
        abort_unless($message->sender_id === $request->user()->id, 403);

        if ($message->image_path) {
            Storage::disk('local')->delete($message->image_path);
        }
        $message->delete();

        return ['ok' => true];
    }

    /**
     * Heel gesprek met één collega in één keer verwijderen (beide kanten,
     * inclusief foto's). Alleen een deelnemer van het gesprek kan dit doen.
     */
    public function destroyThread(Request $request, User $user)
    {
        $me = $request->user()->id;
        abort_if($user->id === $me, 422);

        $query = ChatMessage::where(fn ($q) => $q
            ->where(fn ($a) => $a->where('sender_id', $me)->where('recipient_id', $user->id))
            ->orWhere(fn ($b) => $b->where('sender_id', $user->id)->where('recipient_id', $me)));

        $fotos = (clone $query)->whereNotNull('image_path')->pluck('image_path')->all();
        foreach ($fotos as $pad) {
            Storage::disk('local')->delete($pad);
        }
        $aantal = $query->delete();

        return ['ok' => true, 'deleted' => $aantal];
    }
}
