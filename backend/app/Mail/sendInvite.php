<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class sendInvite extends Mailable
{
	use Queueable, SerializesModels;

	private $userName;
	private $resetLink;

	public function __construct($userName, $resetLink)
	{
		$this->userName = $userName;
		$this->resetLink = $resetLink;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		// Define the view, subject, and content for the email
		return $this->view('emails.sendInvite')
			->subject(__('send_invite.subject'))
			->with(['userName' => $this->userName, 'resetLink' => $this->resetLink]);
	}
}
