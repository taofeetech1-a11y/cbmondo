<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailChange extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $verificationUrl) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new account email')
            ->line('Confirm this address to change your account sign-in and recovery email. Your current address stays active until you confirm.')
            ->action('Confirm email change', $this->verificationUrl)
            ->line('This link expires in 30 minutes. Sign in to the account that requested the change. If you did not request this, ignore this message.');
    }
}
