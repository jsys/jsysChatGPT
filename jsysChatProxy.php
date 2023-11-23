<?php
// Ici on appelle l'api de chatGPT

// Votre clé API à créer et récupérer ici :
// https://platform.openai.com/api-keys
$OPENAI_API_KEY = 'sk-XXX';

// Exemple d'appel à cette page à décomenter pour tester.
//$_POST['messages'] = '[{"role":"user","content":"Quelle est la hauteur de la tour eiffel ?"}]';

if (!isset($_POST['messages'])) {
	die('Erreur. Pas de messages');
}

$messages = json_decode($_POST['messages'], true);

// Ajouter un préprompt système facultatif
array_unshift($messages, [
	"role" => "system",
	"content" => "Tu es un assistant avec beaucoup d'humour"
]);

// On appelle l'api de chatGTP
// Voici l'aide : https://platform.openai.com/docs/api-reference/chat/create
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
	"model" => "gpt-3.5-turbo",
	"stream" => true,
	"messages" => $messages
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Authorization: Bearer ' . $OPENAI_API_KEY,
	'OpenAI-Beta: assistants=v1'
]);
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $chunk) {
	return chat_chunk($chunk);
});
curl_exec($ch);
curl_close($ch);

// Retrourne juste le morceau de texte de la réponse. Voici l'aide :
// https://platform.openai.com/docs/api-reference/chat/streaming
function chat_chunk($chunk) {
	$line = explode("\n", $chunk);
	foreach ($line as $v) {
		if (substr($v, 0, 6) === 'data: ') {
			$txt = trim(substr($v, 6));
			if (substr($txt, 0, 1) == '{') {
				$json = json_decode($txt, true);
				if ($json['choices'][0]['finish_reason'] !== 'stop') {
					echo $json['choices'][0]['delta']['content'];
				}
			}
			ob_flush();
			flush();
		}
	}
	return strlen($chunk);
}