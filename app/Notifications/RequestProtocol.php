<?php


namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RequestProtocol extends Notification implements ShouldQueue
{
    use Queueable;

    private $token;
    private $protocol;
    private $name;
    private $expire;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token, $name,$protocol,$expire)
    {
        $this->token = $token;
        $this->name = $name;
        $this->protocol = $protocol;
        $this->expire = $expire;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {

        $expireFormatted = $this->expire->format('d/m/Y H:i:s');
        return (new MailMessage)
                    ->subject('Pedido de Alteração/Exlusão Recebida!')
                    ->greeting('Olá, ' . $this->name . '!')
                    ->line('Para garantir maior segurança ao seu registro confirme o pedido abaixo:')
                    ->line('Alteração ou exclusão da inscrição de protocolo: '.$this->protocol)
                    ->line('Este pedido expira em: **' . $expireFormatted . '**') // Linha adicionada
                    ->line('Para confirmar a ação por favor clique no link abaixo')
                    // ->action('Alterar / Excluir Registro', route('consume.token', $this->token))
                    ->action('Alterar / Excluir Registro', 'file:///D:/DEV/laravel11-docker/ibict/sysamazonia/tests/forms/editions.html/?token='.$this->token)
                    ->line('Se você tiver alguma dúvida, por favor não hesite em entrar em contato conosco.')
                    ->salutation('Atenciosamente,');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
