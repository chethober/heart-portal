<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffInvitation extends Notification
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hospital = $this->invitation->hospital->name;

        return (new MailMessage)
            ->subject(__('You have been invited to :hospital on :app', ['hospital' => $hospital, 'app' => config('app.name')]))
            ->line(__(':inviter invited you to join :hospital as :role.', [
                'inviter' => $this->invitation->inviter->name,
                'hospital' => $hospital,
                'role' => $this->invitation->role->label(),
            ]))
            ->action(__('Accept invitation'), route('invitations.show', $this->invitation->token))
            ->line(__('This invitation expires on :date.', ['date' => $this->invitation->expires_at->toDayDateTimeString()]));
    }
}
