/**
 * Génère un quiz en utilisant une API d'IA comme OpenAI
 * @param string $content Le contenu des notes à partir duquel générer des questions
 * @param int $num_questions Nombre de questions à générer (par défaut 5)
 * @param string $type Type de quiz (qcm, vrai_faux, texte_libre)
 * @return array|null Tableau de questions ou null en cas d'erreur
 */
function generate_quiz_with_ai($content, $num_questions = 5, $type = 'qcm') {
    // Vérifier que le contenu n'est pas vide
    if (empty($content)) {
        return null;
    }
    
    // Clé API (à stocker de façon sécurisée, idéalement dans des variables d'environnement)
    $api_key = 'votre_clé_api_openai';
    
    // Préparer le prompt selon le type de quiz
    $system_message = "Tu es un assistant spécialisé dans la création de quiz pédagogiques.";
    
    $prompt = "Génère un quiz de $num_questions questions basé sur le contenu suivant. ";
    
    if ($type == 'qcm') {
        $prompt .= "Pour chaque question, fournit 4 options de réponse dont une seule est correcte. ";
        $prompt .= "Retourne le résultat au format JSON suivant: 
        {
            \"questions\": [
                {
                    \"question\": \"Texte de la question\",
                    \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
                    \"correct_answer\": \"Option qui est la bonne réponse\"
                }
            ]
        }";
    } elseif ($type == 'vrai_faux') {
        $prompt .= "Pour chaque question, la réponse doit être Vrai ou Faux. ";
        $prompt .= "Retourne le résultat au format JSON suivant: 
        {
            \"questions\": [
                {
                    \"question\": \"Texte de la question\",
                    \"options\": [\"Vrai\", \"Faux\"],
                    \"correct_answer\": \"Vrai ou Faux\"
                }
            ]
        }";
    } else { // texte_libre
        $prompt .= "Pour chaque question, fournit une réponse correcte attendue. ";
        $prompt .= "Retourne le résultat au format JSON suivant: 
        {
            \"questions\": [
                {
                    \"question\": \"Texte de la question\",
                    \"correct_answer\": \"Réponse attendue\"
                }
            ]
        }";
    }
    
    $prompt .= "\n\nContenu du cours:\n" . $content;
    
    // Préparer la requête pour l'API OpenAI
    $data = [
        'model' => 'gpt-4', // ou gpt-3.5-turbo selon votre abonnement
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_message
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
    ];
    
    // Configuration de la requête cURL
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
    ]);
    
    // Exécuter la requête et récupérer la réponse
    $response = curl_exec($curl);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    // Gérer les erreurs
    if ($error) {
        error_log('Erreur cURL lors de l\'appel à l\'API: ' . $error);
        return null;
    }
    
    // Décoder la réponse JSON
    $response_data = json_decode($response, true);
    
    // Vérifier si la réponse contient une erreur
    if (isset($response_data['error'])) {
        error_log('Erreur API: ' . $response_data['error']['message']);
        return null;
    }
    
    // Extraire le contenu de la réponse
    $ai_response = $response_data['choices'][0]['message']['content'];
    
    // Essayer de décoder le JSON dans la réponse
    try {
        $quiz_data = json_decode($ai_response, true);
        
        // Valider la structure des données
        if (!isset($quiz_data['questions']) || !is_array($quiz_data['questions'])) {
            throw new Exception("Format de réponse invalide");
        }
        
        // S'assurer que chaque question a le bon format
        foreach ($quiz_data['questions'] as &$question) {
            if (!isset($question['question']) || !isset($question['correct_answer'])) {
                throw new Exception("Question mal formatée");
            }
            
            // Pour QCM et Vrai/Faux, vérifier les options
            if ($type != 'texte_libre' && (!isset($question['options']) || !is_array($question['options']))) {
                $question['options'] = [$question['correct_answer']];
                if ($type == 'qcm') {
                    // Générer des options factices si nécessaire
                    $question['options'][] = "Option alternative 1";
                    $question['options'][] = "Option alternative 2";
                    $question['options'][] = "Option alternative 3";
                } elseif ($type == 'vrai_faux') {
                    $question['options'] = ['Vrai', 'Faux'];
                }
            }
        }
        
        return $quiz_data;
        
    } catch (Exception $e) {
        error_log('Erreur de traitement de la réponse: ' . $e->getMessage());
        error_log('Réponse brute: ' . $ai_response);
        return null;
    }
}