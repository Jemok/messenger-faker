<?php

namespace RTippin\MessengerFaker;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\MessengerFaker\Broadcasting\OnlineStatusBroadcast;
use RTippin\MessengerFaker\Broadcasting\ReadBroadcast;
use RTippin\MessengerFaker\Broadcasting\TypingBroadcast;

trait FakerEvents
{
    /**
     * Messages started.
     */
    private function startMessage(): void
    {
        /** @var Participant $participant */
        $participant = $this->participants->random();
        $this->usedParticipants->push($participant);
        $this->setProvider($participant->owner);
        $this->typing($participant->owner);

        if ($this->delay > 0) {
            sleep(1);
        }
    }

    /**
     * Messages ended.
     *
     * @param bool $isFinal
     */
    private function endMessage(bool $isFinal = false): void
    {
        if (! $isFinal) {
            sleep($this->delay);
        } else {
            $this->usedParticipants
                ->unique('owner_id')
                ->each(fn (Participant $participant) => $this->read($participant));
        }
    }

    /**
     * @param bool $useAdmins
     */
    private function setParticipants(bool $useAdmins): void
    {
        if ($useAdmins && $this->thread->isGroup()) {
            $this->participants = $this->thread->participants()->admins()->get();
        } else {
            $this->participants = $this->thread->participants()->get();
        }
    }

    /**
     * @param string $status
     * @param MessengerProvider $provider
     */
    private function setStatus(string $status, MessengerProvider $provider): void
    {
        $online = 0;

        switch ($status) {
            case 'online':
                $this->messenger->setProviderToOnline($provider);
                $online = 1;
            break;
            case 'away':
                $this->messenger->setProviderToAway($provider);
                $online = 2;
            break;
            case 'offline':
                $this->messenger->setProviderToOffline($provider);
            break;
        }

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->name(),
                'online_status' => $online,
            ])
            ->broadcast(OnlineStatusBroadcast::class);
    }

    /**
     * @param Participant $participant
     * @param Message $message
     */
    private function markRead(Participant $participant, Message $message): void
    {
        $this->markRead->withoutDispatches()->execute($participant);

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $participant->owner_id,
                'provider_alias' => $this->messenger->findProviderAlias($participant->owner_type),
                'message_id' => $message->id,
            ])
            ->broadcast(ReadBroadcast::class);
    }

    /**
     * @param MessengerProvider $provider
     */
    private function sendTyping(MessengerProvider $provider): void
    {
        $this->status('online', $provider);

        $this->broadcaster
            ->toPresence($this->thread)
            ->with([
                'provider_id' => $provider->getKey(),
                'provider_alias' => $this->messenger->findProviderAlias($provider),
                'name' => $provider->name(),
                'typing' => true,
            ])
            ->broadcast(TypingBroadcast::class);
    }
}
