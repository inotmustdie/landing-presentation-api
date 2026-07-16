<h2>Your request has been received</h2>
<p>Hello, {{ $contact->name }}!</p>
<p>We received your message and will get back to you soon.</p>
<p><strong>Your comment:</strong> {{ $contact->comment }}</p>
<p><strong>Auto reply:</strong> {{ $insights['reply'] }}</p>
<p><strong>Category:</strong> {{ $insights['category'] }}</p>
