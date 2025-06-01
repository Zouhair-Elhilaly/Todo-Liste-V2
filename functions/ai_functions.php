<?php

/**
 * Génère un quiz en utilisant l'API Gemini de Google
 */
function generate_quiz_with_gemini($content, $question_count, $quiz_type, $module_name) {
    // Remplacez par votre clé API Gemini
    $api_key = 'votre_clé_API'; // À remplacer par votre vraie clé API
    $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
    
    // Construire le prompt selon le type de quiz
    $prompt = build_quiz_prompt($content, $question_count, $quiz_type, $module_name);
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4000,
            'topP' => 0.8,
            'topK' => 40
        ]
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_error($curl)) {
        curl_close($curl);
        throw new Exception('Erreur cURL: ' . curl_error($curl));
    }
    
    curl_close($curl);
    
    if ($http_code !== 200) {
        throw new Exception('Erreur API Gemini: HTTP ' . $http_code . ' - ' . $response);
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Réponse invalide de l\'API Gemini');
    }
    
    $generated_text = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // Parser la réponse JSON de Gemini
    return parse_gemini_response($generated_text, $quiz_type, $module_name);
}

/**
 * Construit le prompt pour Gemini selon le type de quiz
 */
function build_quiz_prompt($content, $question_count, $quiz_type, $module_name) {
    $base_prompt = "Tu es un expert en création de quiz éducatifs. ";
    $base_prompt .= "Basé sur le contenu suivant du module '$module_name', ";
    $base_prompt .= "crée exactement $question_count questions de type '$quiz_type'.\n\n";
    $base_prompt .= "CONTENU DU MODULE:\n" . $content . "\n\n";
    
    switch ($quiz_type) {
        case 'qcm':
            $prompt = $base_prompt . "
INSTRUCTIONS POUR QCM:
- Crée exactement $question_count questions à choix multiples
- Chaque question doit avoir exactement 4 options (A, B, C, D)
- Une seule réponse correcte par question
- Les questions doivent couvrir différents aspects du contenu
- Inclus une explication pour chaque réponse correcte

FORMAT DE RÉPONSE REQUIS (JSON strict):
{
    \"title\": \"Quiz QCM - $module_name\",
    \"questions\": [
        {
            \"question\": \"Votre question ici?\",
            \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
            \"correct_answer\": \"Option correcte\",
            \"explanation\": \"Explication de la réponse\"
        }
    ]
}";
            break;
            
        case 'vrai_faux':
            $prompt = $base_prompt . "
INSTRUCTIONS POUR VRAI/FAUX:
- Crée exactement $question_count affirmations
- Chaque affirmation doit pouvoir être répondue par Vrai ou Faux
- Mélange équitablement les réponses vraies et fausses
- Inclus une explication pour chaque réponse

FORMAT DE RÉPONSE REQUIS (JSON strict):
{
    \"title\": \"Quiz Vrai/Faux - $module_name\",
    \"questions\": [
        {
            \"question\": \"Votre affirmation ici\",
            \"options\": [\"Vrai\", \"Faux\"],
            \"correct_answer\": \"Vrai\" ou \"Faux\",
            \"explanation\": \"Explication de la réponse\"
        }
    ]
}";
            break;
            
        case 'texte_libre':
            $prompt = $base_prompt . "
INSTRUCTIONS POUR RÉPONSE LIBRE:
- Crée exactement $question_count questions ouvertes
- Les questions doivent encourager des réponses détaillées
- Fournis une réponse modèle pour chaque question
- Les questions doivent tester la compréhension approfondie

FORMAT DE RÉPONSE REQUIS (JSON strict):
{
    \"title\": \"Quiz Réponse Libre - $module_name\",
    \"questions\": [
        {
            \"question\": \"Votre question ouverte ici?\",
            \"correct_answer\": \"Réponse modèle détaillée\",
            \"explanation\": \"Points clés à inclure dans la réponse\"
        }
    ]
}";
            break;
    }
    
    $prompt .= "\n\nIMPORTANT: Réponds UNIQUEMENT avec du JSON valide, sans texte supplémentaire avant ou après.";
    
    return $prompt;
}

/**
 * Parse la réponse de Gemini
 */
function parse_gemini_response($response, $quiz_type, $module_name) {
    // Nettoyer la réponse (enlever les marqueurs markdown éventuels)
    $response = trim($response);
    $response = preg_replace('/^```json\s*/', '', $response);
    $response = preg_replace('/\s*```$/', '', $response);
    
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception('Impossible de parser la réponse JSON de Gemini: ' . json_last_error_msg());
    }
    
    // Valider la structure
    if (!isset($data['questions']) || !is_array($data['questions'])) {
        throw new Exception('Structure de réponse invalide: questions manquantes');
    }
    
}