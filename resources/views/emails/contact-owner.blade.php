<h2>New contact request</h2>
<p><strong>ID:</strong> {{ $contact->contactId }}</p>
<p><strong>Name:</strong> {{ $contact->name }}</p>
<p><strong>Email:</strong> {{ $contact->email }}</p>
<p><strong>Phone:</strong> {{ $contact->phone }}</p>
<p><strong>Comment:</strong> {{ $contact->comment }}</p>
<p><strong>Sentiment:</strong> {{ $insights['sentiment'] }}</p>
<p><strong>Category:</strong> {{ $insights['category'] }}</p>
<p><strong>Summary:</strong> {{ $insights['summary'] }}</p>
<p><strong>AI provider:</strong> {{ $insights['ai_provider'] }}</p>
