<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Usuario;

class RecuperarSenha extends Mailable
{
    use Queueable, SerializesModels;

    public $usuario;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Usuario $usuario)
    {
        $this->usuario = $usuario;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'))
            ->view('emails.recuperar_senha');
    }
}
