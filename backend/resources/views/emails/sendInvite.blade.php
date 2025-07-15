<!DOCTYPE html>
<html>

<head>
	<title>{{ __('send_invite.title') }}</title>
</head>

<body>
	<table
		style="width: 100%; background-color: #FCFCFC; border-collapse: collapse; border: 1px solid #0d203a; border-radius: 4px; max-width: 450px;">
		<tr>
			<td style="padding: 20px 0 12px 20px; background-color: #0d203a">
				<img src="{{ env('URL_BACKEND') . '/storage/logo-inverse.png' }}" alt="HandBi" width="100"
					style="display: block; margin: 0;">
			</td>
		</tr>
		<tr>
			<td style="padding: 0 20px">
				<h2 style="color:#0d203a; margin: 25px 0 0 0;">{{ __('send_invite.title') }}</h2>
			</td>
		</tr>
		<tr>
			<td style="padding: 0; margin: 3px 0;">
				<p style="padding: 0 20px; color: #444444;">
					{{ __('send_invite.line_1', ['userName' => $userName]) }}<br>
					{{ __('send_invite.line_2') }}
				</p>
			</td>
		</tr>
		<tr>
			<td style="padding: 0; margin: 3px 0;">
				<a href="{{ $resetLink }}"
					style="margin: 15px 20px; padding: 15px 20px; background-color: #0e62a4; color: #FFFFFF; border-radius: 4px; display: inline-block;">
					{{ __('send_invite.button_label') }}
				</a>
			</td>
		</tr>
		<tr>
			<td style="padding: 0; margin: 3px 0;">
				<p style="padding: 0 20px; color: #444444;">
					{{ $resetLink }}
				</p>
			</td>
		</tr>
		<tr>
			<td style="padding: 20px; color: #444444;">
				<p></p>
				<p style="padding: 0; margin: 0;">{{ __('send_invite.farewell') }},</p>
				<p style="padding: 0; margin: 0;"><strong>{{ __('send_invite.signature') }}</strong></p>
			</td>
		</tr>
	</table>
</body>

</html>