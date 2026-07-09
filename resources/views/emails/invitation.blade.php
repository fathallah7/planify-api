<!DOCTYPE html>
<html>

<body>
  <h1>You're invited to join {{ $tenant->name }}</h1>
  <p>You have been invited to join as {{ $invitation->role }}.</p>
  <p>This invitation expires in 7 days.</p>
  <a href="{{ config('app.url') }}/api/invitations/accept/{{ $invitation->token }}">
    Accept Invitation
  </a>
</body>

</html>
