<?php

/**
* This file is part of Cria.
* Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
* Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
* You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
*
* @package    local_cria
* @author     Patrick Thibaudeau
* @copyright  2024 onwards York University (https://yorku.ca)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


$string['about'] = 'À propos';
$string['actions'] = 'Actions';
$string['add'] = 'Ajouter';
$string['add_bot'] = 'Créer un nouveau bot';
$string['add_content'] = 'Ajouter du contenu';
$string['add_document'] = 'Ajouter un document';
$string['add_entity'] = 'Ajouter une entité';
$string['add_intent'] = 'Ajouter une intention';
$string['add_keyword'] = 'Ajouter un mot-clé';
$string['add_model'] = 'Ajouter un modèle';
$string['add_provider'] = 'Ajouter un fournisseur';
$string['add_question'] = 'Ajouter une question';
$string['add_type'] = 'Ajouter un type';
$string['available_child'] = 'Rendre ce bot disponible pour d\'autres bots';
$string['available_child_help'] = 'Sélectionnez Oui si vous voulez que ce bot soit disponible pour d\'autres bots en tant que bot enfant. Cela est utile si vous souhaitez créer un bot qui utilise le contenu d\'un autre bot.';
$string['advanced_settings'] = 'Paramètres avancés';
$string['all'] = 'Tout';
$string['answer'] = 'Réponse';
$string['audience'] = 'Public';
$string['auto_test'] = 'Auto-Test';
$string['automated_tasks'] = 'Tâches automatisées';
$string['assign_users'] = 'Attribuer des utilisateurs';
$string['ask_a_question'] = 'Poser une question';
$string['azure_api_version'] = 'Version de l\'API Azure OpenAI';
$string['azure_deployment_name'] = 'Nom du déploiement Azure';
$string['azure_endpoint'] = 'Point de terminaison Azure';
$string['azure_endpoint_help'] = 'URL du point de terminaison Azure';
$string['azure_key'] = 'Clé Azure';
$string['azure_key_help'] = 'Utilisez l\'une des deux clés disponibles pour le service OpenAI dans Azure';
$string['bot'] = 'Bot';
$string['bot_already_exists'] = 'Un bot avec cet identifiant existe déjà.';
$string['bot_api_key'] = 'Clé API';
$string['bot_api_key_instructions'] = 'La clé API et le nom du bot sont destinés à être utilisés directement avec CriaBot. Cela n\'est nécessaire que si vous souhaitez intégrer votre propre IA dans une autre application logicielle.' .
    ' Si vous avez besoin d\'une clé API pour Cria, qui permet des appels directs aux LLM, veuillez contacter l\'administrateur. Si vous souhaitez intégrer ce bot sur une page Web, cliquez sur le bouton "Partager" pour ce bot sur le tableau de bord et copiez le code d\'intégration.' .
    ' <br><br><b>NE MODIFIEZ PAS CES VALEURS. VOUS RISQUEZ DE CASSER VOTRE BOT EXISTANT !</b>';
$string['bot_api_key_help'] = 'La clé API est destinée à être utilisée directement avec CriaBot et non avec Cria elle-même. Si vous avez besoin d\'une clé API pour Cria, veuillez contacter l\'administrateur.';
$string['bot_configuration'] = 'BotCraft';
$string['bot_configuration_help'] = 'Créez facilement un bot en fournissant votre propre documentation et vos messages système.';
$string['bot_configurations'] = 'Configurations de bot';
$string['bot_locale'] = 'Langue de synthèse vocale';
$string['bot_locale_help'] = 'Sélectionnez la langue dans laquelle vous souhaitez que le bot parle.';
$string['bot_models'] = 'Modèles';
$string['bot_name'] = 'Nom du bot (utilisé avec CriaBot)';
$string['bot_personality'] = 'Personnalité du bot';
$string['bot_type'] = 'Type de bot';

$string['bot_types'] = 'Types de bots';
$string['bot_type_factual'] = 'Factuel';
$string['bot_type_transcription'] = 'Transcription';
$string['bot_type_help'] = 'Le type de bot définit la personnalité du bot et le type de contenu qu\'il peut traiter.';
$string['bot_system_message'] = 'Message système';
$string['bot_system_message_help'] = 'Entrez une invite qui décrit ce que fait votre bot. Ce message est généralement conçu pour être informatif, contextuellement pertinent et contribuer à un dialogue plus naturel et cohérent entre l\'utilisateur et le modèle.';
$string['bot_watermark'] = 'Afficher le filigrane Cria';
$string['bot_watermark_help'] = 'Sélectionnez Oui si vous souhaitez que le filigrane Cria soit affiché sur le bot intégré.';
$string['bot_content_training_framework'] = 'Cadre de formation de contenu';
$string['bots'] = 'Bots';
$string['cachedef_cria_system_messages'] = 'Met en cache tous les messages système pour les bots';
$string['cancel'] = 'Annuler';
$string['chatbot_framework'] = 'Cadre de chatbot';
$string['chat_does_not_exist'] = 'Le chat demandé n\'existe pas.';
$string['child_bots'] = 'Bots enfants';
$string['child_bots_help'] = 'Sélectionnez les bots que vous souhaitez rendre disponibles pour ce bot. Cela est utile si vous souhaitez créer un bot qui utilise le contenu d\'un autre bot.';
$string['chunk_limit'] = 'Nombre de mots par segment';
$string['chunk_limit_help'] = 'OpenAI fonctionne sur des segments de texte. Ce paramètre définit le nombre de mots par segment.<br>Pour GPT-3.5-turbo 16k, le nombre maximum de mots par segment est de 12000.';
$string['close'] = 'Fermer';
$string['cohere_api_key'] = 'Clé API Cohere';
$string['cohere_api_key_help'] = 'Clé API Cohere requise pour que le classement et le reclassement fonctionnent. Sans cette clé, vous ne pourrez jamais empêcher l\'IA de générer des réponses.';
$string['column_name_must_exist'] = 'La colonne <b>${a}</b> est manquante. Elle doit exister dans le fichier que vous importez.';
$string['compare_text_bot_id'] = 'Comparer l\'ID du bot de texte';
$string['compare_text_bot_id_help'] = 'Entrez l\'ID du bot à utiliser pour l\'exigence de comparaison de texte lors de l\'utilisation de bots non indexés.';
$string['completion_cost'] = 'Coût de complétion';
$string['completion_tokens'] = 'Jetons de complétion';
$string['content'] = 'Contenu';
$string['content_for'] = 'Contenu pour';
$string['cost'] = 'Coût';
$string['conversation_styles'] = 'Styles de conversation';
$string['create_example_questions'] = 'Faire créer 5 questions d\'exemple par l\'IA ?';
$string['create_meeting_notes'] = 'MinutesMaster';
$string['create_meeting_notes_help'] = 'Utilisez cet outil pour créer des notes de réunion basées sur une transcription';
$string['cria_suite'] = 'Suite Cria';
$string['date_range'] = 'Plage de dates';
$string['debugging'] = 'Débogage';
$string['default_no_context_message'] = "Je suis désolé, je n\'ai pas de réponse à cette question. Veuillez essayer de poser une autre question.";
$string['default_user_prompt'] = 'Invite utilisateur par défaut';
$string['default_user_prompt_help'] = 'Si votre bot a une invite par défaut, entrez-la ici. Si l\'invite utilisateur requise ci-dessus est définie sur Oui, cette invite sera ajoutée à l\'invite utilisateur. Notez qu\'elle n\'est pas visible sur la page.';
$string['deployment_name'] = 'Nom du déploiement';
$string['deployment_name_help'] = 'Le nom du déploiement du modèle dans Azure';
$string['delete'] = 'Supprimer';
$string['delete_all'] = 'Tout supprimer';
$string['delete_document_confirmation'] = 'Êtes-vous sûr de vouloir supprimer le(s) document(s) sélectionné(s) ?';
$string['delete_entity_help'] = 'Êtes-vous sûr de vouloir supprimer cette entité ?';
$string['delete_keyword_help'] = 'Êtes-vous sûr de vouloir supprimer ce mot-clé ?';
$string['delete_selected'] = 'Supprimer la sélection';
$string['delete_selected_documents'] = 'Supprimer les documents sélectionnés';
$string['description'] = 'Description';
$string['development'] = 'Développement';
$string['display_settings'] = 'Paramètres d\'affichage';
$string['documents'] = 'Documents';
$string['download'] = 'Télécharger';
$string['edit'] = 'Modifier';
$string['edit_bot'] = 'Modifier le bot';
$string['edit_content'] = 'Modifier le contenu';
$string['edit_entity'] = 'Modifier l\'entité';
$string['edit_intent'] = 'Modifier l\'intention';
$string['edit_keyword'] = 'Modifier le mot-clé';
$string['edit_question'] = 'Modifier la question';
$string['embed_code'] = 'Code d\'intégration';
$string['embed_code_help'] = 'Copiez et collez le code ci-dessous sur vos pages web pour intégrer le chatbot.';
$string['embedding'] = 'Intégration';
$string['embedding_server_url'] = 'URL du serveur d\'intégration';
$string['embedding_server_url_help'] = 'URL du serveur d\'intégration';
$string['entities'] = 'Entités';
$string['entity'] = 'Entité';
$string['error_importfile'] = 'Une erreur s\'est produite lors de l\'importation du fichier';
$string['error_message'] = 'Message d\'erreur';
$string['event_file_created'] = 'Événement Cria : Fichier créé';
$string['examples'] = 'Exemples';
$string['existing_bots'] = 'Bots existants';
$string['existing_bot_models'] = 'Modèles existants';
$string['existing_bot_types'] = 'Types de bots existants';
$string['export_questions'] = 'Exporter les questions';
$string['faculties'] = 'Facultés';
$string['faculties_help'] = 'Une ligne par faculté. Chaque ligne doit être au format suivant :<br>' .
    '<b>Acronyme de la faculté</b> pipe (|) <b>Nom de la faculté</b> <br><br>' .
    'Exemple :<br>' .
    'ED|Faculté d\'éducation';
$string['faculty'] = 'Faculté';
$string['file'] = 'Fichier';
$string['files'] = 'Fichiers';
$string['fine_tuning'] = 'Ajustement fin';
$string['fine_tuning_help'] = 'L\'ajustement fin vous fournira des paramètres supplémentaires pour affiner votre modèle. Il vous permettra également' .
    ' de séparer votre contenu en catégories, rendant votre bot plus précis et exact.' .
    ' C\'est une fonctionnalité avancée qui ne doit être utilisée que si vous savez ce que vous faites.';
$string['for_developers'] = 'Pour les développeurs';
$string['generate_answer'] = 'Laisser l\'IA générer une réponse basée sur votre réponse ci-dessus ?';
$string['generate_answer_help'] = 'Si oui, l\'IA générera une réponse basée sur la réponse que vous avez fournie ci-dessus.'
    . ' Si non, la réponse ci-dessus sera fournie. REMARQUE : si vous utilisez des images, des cartes, des vidéos ou d\'autres médias, vous devez toujours sélectionner non.';
$string['generate_synonyms'] = 'Laisser l\'IA générer des synonymes pour ce mot-clé ?';
$string['generate_synonyms_help'] = 'Si oui est sélectionné, l\'IA fera de son mieux pour générer des synonymes pour ce mot-clé.';
$string['gpt_cost'] = 'Coût GPT ?';
$string['gpt_cost_help'] = 'Coût de GPT par 1000 tokens';
$string['groups'] = 'Groupes';
$string['icon_url'] = 'URL de l\'icône';

$string['import_questions'] = 'Importer des questions';
$string['index_context'] = 'Contexte de l\'index';
$string['index_files'] = 'Indexer les fichiers';
$string['indexing_failed'] = 'Erreur';
$string['indexing_pending'] = 'En attente';
$string['indexing_server_api_key'] = 'Clé API du serveur d\'indexation';
$string['indexing_server_api_key_help'] = 'Entrez la clé API pour le serveur d\'indexation';
$string['indexing_server_url'] = 'URL du serveur d\'indexation';
$string['indexing_server_url_help'] = 'URL du serveur d\'indexation';
$string['indexing_started'] = 'Entraînement';
$string['indexing_success'] = 'Entraîné';
$string['ip'] = 'IP';
$string['is_embedding'] = 'Ceci est un modèle d\'intégration';
$string['intent'] = 'Intention';
$string['intents'] = 'Intentions';
$string['keyword'] = 'Mot-clé';
$string['keywords'] = 'Mots-clés';
$string['languages'] = 'Langues';
$string['languages_help'] = 'Une ligne par langue. Chaque ligne doit être au format suivant :<br>' .
    '<b>Code de la langue</b> pipe (|) <b>Nom de la langue</b> <br><br>' .
    'Exemple :<br>' .
    'en|Anglais';
$string['llm_models'] = 'Modèles LLM';
$string['logs'] = 'Journaux';
$string['logs_for'] = 'Journaux pour';
$string['long'] = 'Long';
$string['medium'] = 'Moyen';
$string['message'] = 'Message';
$string['model'] = 'Modèle';
$string['model_max_tokens'] = 'Nombre maximum de tokens du modèle';
$string['model_max_tokens_help'] = 'Nombre maximum de tokens que ce modèle peut générer.' .
    '<br> Pour GPT-3.5-turbo 4k : 4096.' .
    '<br> Pour GPT-3.5-turbo 16k : 16384.' .
    '<br> Pour GPT-4 : 8192.' .
    '<br> Pour GPT-4-32k : 32768.';
$string['model_name'] = 'Nom du modèle';
$string['name'] = 'Nom';
$string['new_category'] = 'Nouvelle catégorie';
$string['new_role'] = 'Nouveau rôle';
$string['no_context_email_message'] = '<p>Le bot {$a->bot_name} n\'a pas pu fournir de réponse pour la question : {$a->prompt}.</p>';
$string['no_context_email_message_llm_guess'] = '<p>Cependant, comme la fonctionnalité de devinette LLM est activée, il a retourné '
    . 'cette réponse :</p><p>{$a->answer}</p>';
$string['no_context_message'] = 'Message sans résultat';
$string['no_context_subject'] = 'Aucun résultat retourné par le bot';
$string['no_context_use_message'] = 'Utiliser le message sans contexte';
$string['no_context_use_message_help'] = 'Sélectionnez Oui si vous souhaitez utiliser le message sans contexte. Si vous sélectionnez Non, ' .
    ' le bot retournera toujours une réponse. Remarque : Cela pourrait entraîner des hallucinations et des informations erronées.';
$string['no_context_email'] = 'Email de notification sans contexte';
$string['no_context_email_help'] = 'Entrez l\'adresse email pour recevoir des notifications lorsque le bot n\'a pas de réponse (contexte).';
$string['no_context_llm_guess'] = 'Utiliser la devinette LLM';

$string['no_context_llm_guess_help'] = 'Sélectionnez Oui si vous souhaitez que le bot devine une réponse.';
$string['nodes'] = 'Nœuds';
$string['parse_strategy'] = 'Stratégie de prétraitement';
$string['parse_strategy_help'] = 'Sélectionnez le type de prétraitement que vous souhaitez utiliser.';
$string['paste_text'] = 'Collez votre texte ici';
$string['permissions'] = 'Autorisations';
$string['plugin_path'] = 'Chemin du plugin';
$string['pluginname'] = 'Cria';
$string['privacy:metadata'] = 'Ce plugin ne stocke aucune donnée personnelle.';
$string['process'] = 'Processus';
$string['program'] = 'Programme';
$string['programs'] = 'Programmes';
$string['programs_help'] = 'Une ligne par programme. Chaque ligne doit être au format suivant :<br>' .
    '<b>Acronyme du programme</b> pipe (|) <b>Nom du programme</b> <br><br>' .
    'Exemple :<br>' .
    'BEd|Baccalauréat en éducation';
$string['prompt'] = 'Invite';
$string['prompt_cost'] = 'Coût de l\'invite';
$string['prompt_settings'] = 'Paramètres de l\'invite';
$string['prompt_tokens'] = 'Jetons de l\'invite';
$string['provider'] = 'Fournisseur';
$string['provider_image'] = 'Image du fournisseur';
$string['providers'] = 'Fournisseurs';
$string['publish'] = 'Publier';
$string['publish_all'] = 'Publier tous les fichiers';
$string['publish_document_confirm'] = 'Êtes-vous sûr de vouloir publier le(s) document(s) sélectionné(s) ?';
$string['publish_questions'] = 'Publier les questions';
$string['publish_questions_confirmation'] = 'Êtes-vous sûr de vouloir publier la(les) question(s) sélectionnée(s) ?';
$string['question'] = 'Question';
$string['questions'] = 'Questions';
$string['question_for'] = 'Questions pour';
$string['related_questions'] = 'Questions connexes';
$string['related_questions_help'] = '<p>Les questions connexes sont des questions de suivi liées à la question principale. ' .
    'Ces questions sont utilisées pour aider le demandeur à trouver la bonne réponse. Le champ ne capturera que les trois premières questions</p>' .
    '<p>Entrez une question par ligne. Chaque question doit être au format suivant :</p>' .
    '<b>Étiquette</b> pipe (|) <b>Question (invite)</b> <br><br>' .
    'Exemple :<br>' .
    'Capitale du Canada!|Quelle est la capitale du Canada ?';
$string['related_prompts'] = 'Questions de démarrage';
$string['related_prompts_help'] = 'Vous pouvez ajouter jusqu\'à 6 questions/invites de démarrage. Ces questions seront affichées à l\'utilisateur lorsque le bot sera démarré pour la première fois.'
    . 'Les questions doivent être au format JSON suivant :<br>'
    . '<pre>'
    . '[{"label":"un nom d\'affichage", "prompt":"la question d\'invite"},{"label":"un autre nom d\'affichage", "prompt":"une autre question d\'invite"}]'
    . '</pre>';
$string['requires_content_prompt'] = 'Nécessite une invite de contenu';
$string['requires_content_prompt_help'] = 'Sélectionnez Oui si vous souhaitez une zone de texte pour coller du contenu pouvant être utilisé avec une invite utilisateur';
$string['requires_user_prompt'] = 'Nécessite une invite utilisateur';
$string['requires_user_prompt_help'] = 'Sélectionnez Oui si vous souhaitez une zone de texte pour entrer une invite utilisateur. Si vous sélectionnez Non,' .
    ' assurez-vous de définir une invite utilisateur par défaut ci-dessous.';
$string['rerank_model_id'] = 'Modèle de rerank';

$string['response'] = 'Réponse';
$string['response_length'] = 'Longueur de la réponse';
$string['retrieved_from'] = 'Récupéré de';
$string['return'] = 'Retour';
$string['role'] = 'Rôle';
$string['role_description'] = 'Description du rôle';
$string['role_name'] = 'Nom du rôle';
$string['role_permissions'] = 'Permissions du rôle';
$string['role_shortname'] = 'Nom court du rôle';
$string['save'] = 'Enregistrer';
$string['save_and_publish'] = 'Enregistrer et publier';
$string['select'] = 'Sélectionner';
$string['select_a_provider'] = 'Sélectionnez un fournisseur pour continuer.';
$string['share'] = 'Partager';
$string['short'] = 'Court';
$string['submit'] = 'Soumettre';
$string['subtitle'] = 'Sous-titre';
$string['statistics'] = 'Statistiques';
$string['status'] = 'Statut';
$string['support_email'] = 'Email de support';
$string['support_email_help'] = 'Entrez l\'adresse email d\'une personne qui doit être notifiée en cas d\'erreur lors d\'un chat.';
$string['synonym'] = 'Synonyme';
$string['synonyms'] = 'Synonymes';
$string['system_message'] = 'Message système';
$string['system_reserved'] = 'Réservé au système';
$string['take_me_there'] = 'Allons-y!';
$string['test_bot'] = 'Tester le bot';
$string['theme_color'] = 'Couleur du thème';
$string['testing_bot'] = 'Test du bot';
$string['title'] = 'Titre';
$string['tone'] = 'Ton';
$string['total_tokens'] = 'Total des jetons';
$string['total_usage_cost'] = 'Coût total d\'utilisation';
$string['total_words'] = 'Nombre de mots dans le contenu combiné:';
$string['update'] = 'Mettre à jour';
$string['timecreated'] = 'Heure de création';
$string['translate'] = 'Traduire';
$string['upload_files'] = 'Télécharger des fichiers';
$string['use_bot_server'] = 'Nécessite le téléchargement de documents?';
$string['use_bot_server_help'] = 'Si ce type nécessite le téléchargement de documents, sélectionnez Oui.';
$string['user_prompt'] = 'Invite utilisateur';
$string['userid'] = 'ID utilisateur';
$string['view_parsing_data'] = 'Voir les données de parsing';
$string['web_page_help'] = 'Entrez les adresses des pages web incluant http/https. Séparez chaque adresse par une nouvelle ligne.<br>'
    . '<br>Veuillez noter que certaines pages web peuvent ne pas permettre la capture de leur contenu. '
    . 'Si vous rencontrez un message "aucun contenu" lors du test de votre bot, envisagez de télécharger le document généré pour '
    . 'vérifier le contenu.';
$string['web_pages'] = 'Pages web';
$string['welcome_message'] = 'Message de bienvenue';
$string['welcome_message_help'] = 'Le message de bienvenue à afficher lorsque le bot est utilisé';
$string['word_count'] = 'Nombre de mots';


// GPT Settings
$string['max_tokens'] = 'Nombre maximum de jetons';
$string['max_tokens_help'] = 'Le nombre maximum de jetons à générer. Le maximum dépend du cadre de travail du chatbot sélectionné.' .
    ' Lors de la sélection d\'un cadre de travail de chatbot, le nombre maximum de jetons sera affiché.' .
    ' Vous devriez éviter d\'utiliser le nombre maximum de jetons car cela augmentera le coût.' .
    ' Les réponses peuvent également être trop longues. Si vous trouvez qu\'elles sont trop longues, réduisez le nombre maximum de jetons.';
$string['min_k'] = 'Min K';
$string['temperature'] = 'Température';
$string['temperature_help'] = 'Plus la température est élevée, plus le texte est fou. Il est recommandé d\'expérimenter avec des valeurs comprises entre 0,1 et 1,2.';
$string['top_p'] = 'Top P';
$string['top_p_help'] = 'Une alternative à l\'échantillonnage "Top K", cela arrêtera la complétion lorsque la probabilité cumulative des jetons générés dépasse la valeur.';
$string['top_k'] = 'Top K';
$string['top_n'] = 'Top N';
$string['top_k_help'] = 'Une alternative à l\'échantillonnage avec température, appelée "échantillonnage par noyau", où le modèle considère les résultats des jetons avec la masse de probabilité top_k. Donc 0,1 signifie que seuls les jetons comprenant les 10% de masse de probabilité les plus élevés sont pris en compte.';
$string['min_relevance'] = 'Pertinence minimale';
$string['min_relevance_help'] = 'La pertinence minimale de la réponse. Plus le nombre est élevé, plus la réponse sera pertinente.';
$string['max_context'] = 'Contexte maximum';
$string['max_context_help'] = '<b>NE CHANGEZ PAS CETTE VALEUR!!!</b><br>' .
    'Le nombre maximum de jetons que le bot peut gérer sans générer d\'erreur.' .
    ' Cela est basé sur le cadre de travail du chatbot et mis à jour automatiquement lors de la sélection d\'un cadre.' .
    ' <p><b>Ne changez cette valeur que si vous savez exactement ce que vous faites!</b></p>';


// Capabilites
$string['cria:bot_permissions'] = 'Autorisations du bot : Accorde à l\'utilisateur la capacité de modifier les autorisations pour un bot';
$string['cria:delete_bots'] = 'Supprimer les bots : Accorde la permission de supprimer les bots';
$string['cria:edit_bots'] = 'Ajouter/Modifier les bots : Accorde la permission d\'ajouter/modifier les bots';
$string['cria:test_bots'] = 'Tester les bots : Accorde la permission de tester les bots';
$string['cria:view_bots'] = 'Voir les bots : Accorde la permission de voir les bots';
$string['cria:delete_bot_types'] = 'Supprimer les types de bots : Accorde la permission de supprimer les types de bots';
$string['cria:edit_bot_types'] = 'Ajouter/Modifier les types de bots : Accorde la permission d\'ajouter/modifier les types de bots';
$string['cria:view_bot_types'] = 'Voir les types de bots : Accorde la permission de voir les types de bots';
$string['cria:delete_bot_content'] = 'Supprimer le contenu des bots : Accorde la permission de supprimer le contenu des bots';
$string['cria:edit_bot_content'] = 'Ajouter/Modifier le contenu des bots : Accorde la permission d\'ajouter/modifier le contenu des bots';
$string['cria:view_bot_logs'] = 'Voir les journaux des bots : Accorde la permission de voir les journaux des bots';
$string['cria:delete_models'] = 'Supprimer les modèles : Accorde la permission de supprimer les modèles';
$string['cria:edit_models'] = 'Ajouter/Modifier les modèles : Accorde la permission d\'ajouter/modifier les modèles';
$string['cria:view_models'] = 'Voir les modèles : Accorde la permission de voir les modèles';
$string['cria:edit_system_reserved'] = 'Modifier les bots réservés au système : Accorde la permission de modifier les bots réservés au système';
$string['cria:share_bots'] = 'Partager les bots : Accorde la permission de partager les bots';
$string['cria:translate'] = 'Traduire : Accorde la permission de traduire le texte';
$string['cria:groups'] = 'groupes';
$string['cria:view_providers'] = 'groupes';
$string['cria:view_advanced_bot_options'] = 'Voir les options avancées des bots';
$string['cria:view_conversation_styles'] = 'Voir les styles de conversation';


// Error messages
$string['default_error_message'] = 'Je ne suis pas sûr de ce qui s\'est passé, mais je ne peux pas répondre à cette question. J\'ai informé mon administrateur. En attendant, veuillez poser une autre question.';
$string['error_message_body'] = 'L\'erreur suivante s\'est produite pour le bot : {$a->bot_name}<br><br>Invite : {$a->prompt}<br><br>Erreur : {$a->error_message}';
$string['error_message_subject'] = 'ERREUR ! Le bot n\'a pas pu répondre';
$string['openai_filter'] = 'Oups ! Il semble que vous essayez d\'utiliser un mot qui n\'est pas autorisé. Veuillez reformuler votre question.';

// Settings
$string['criabot_url'] = 'URL de CriaBot';
$string['criabot_url_help'] = 'Entrez l\'URL de l\'instance CriaBot à laquelle vous vous connectez.';
$string['criadex_url'] = 'URL de CriaDex';
$string['criadex_url_help'] = 'Entrez l\'URL de l\'instance CriaDex à laquelle vous vous connectez.';
$string['criaembed_url'] = 'URL de CriaEmbed';
$string['criaembed_url_help'] = 'Entrez l\'URL de l\'instance CriaEmbed à laquelle vous vous connectez.';
$string['criadex_api_key'] = 'Clé API de CriaDex';
$string['criadex_api_key_help'] = 'Entrez la clé API de l\'instance CriaDex à laquelle vous vous connectez. ' .
    'Remarque : DOIT ÊTRE UNE CLÉ MAÎTRE<br> ';
$string['criaparse_url'] = 'URL de CriaParse';
$string['criaparse_url_help'] = 'Entrez l\'URL de l\'instance CriaParse à laquelle vous vous connectez.';
$string['criascraper_url'] = 'URL de CriaScraper';
$string['criascraper_url_help'] = 'Entrez l\'URL de l\'instance CriaScraper à laquelle vous vous connectez.';

// MinutesMaster
$string['convert'] = 'Convert';
$string['date'] = 'Date';
$string['date_help'] = 'Optional: Enter the time in your preferred format.';
$string['info'] = 'MinutesMaster';
$string['info_text'] = 'Use the following form to create meeting minutes based on your notes or a transcription.';
$string['location'] = 'Location';
$string['location_help'] = 'Optional: Enter the location the meeting took place. This can be a physical location or a virtual location.';
$string['language'] = 'Language';
$string['language_help'] = 'Select the language in which you would like the results to be returned. Note: Make sure that your template provides support for the language you have chosen.';
$string['minutes_master'] = 'MinutesMaster';
$string['minutes_master_id'] = 'MinutesMaster ID';
$string['minutes_master_id_help'] = 'The bot id to use for MinutesMaster';
$string['no_bot_id'] = 'BOT ID MISSING';
$string['no_bot_defined'] = 'No bot has been defined for MinutesMaster. Please contact the administrator.';
$string['notes'] = 'Notes/Trasncription';
$string['notes_help'] = 'Copy/Paste your notes or transcription here.';
$string['process_notes'] = 'Process notes/transcription';
$string['process_notes_help'] = 'Click this button to process your notes/transcription.';
$string['project_name'] = 'Project name';
$string['project_name_help'] = 'Optional: Enter the name of your project. This will also be used to name the file.';
$string['time'] = 'Time';
$string['time_help'] = 'Optional: Enter the time in your preferred format.';

// Translate
$string['academic'] = 'Academic';
$string['formal'] = 'Formal';
$string['informal'] = 'Informal';
$string['literature'] = 'Literary';
$string['paraphrase'] = 'Paraphrase';
$string['paraphrase_help'] = 'Selecting Yes will rewrite/paraphrase the text using the voice selected.';
$string['paraphrase_text'] = 'Rewrite text';
$string['translate'] = 'Translate';
$string['translate_id'] = 'Translate ID';
$string['translate_id_help'] = 'Enter the bot id used for the translation app';
$string['translate_to'] = 'Translate to';
$string['translation'] = 'Translation';
$string['translation_app'] = 'LinguaLlama';
$string['translation_app_help'] = 'LinguaLlama allows you to quickly translate or paraphrase a text using various writing styles.';
$string['unchanged'] = 'None';
$string['voice'] = 'Voice';
$string['voice_help'] = 'Select the voice you would like your translation in';

// SecondOpinion
$string['secondopinion_id'] = 'SecondOpinion Bot ID';
$string['secondopinion_id_help'] = 'Enter the Bot ID for SecondOpinion';
$string['secondopinion'] = 'SecondOpinion';
$string['rubric'] = 'Rubric';
$string['rubric_help'] = 'Paste your rubric here. The rubric format must be the following:<br><p>Skill (X point)<br><br>Description</p>';
$string['assignment'] = 'Assignment';
$string['assignment_help'] = 'Paste the student assignment here. Maximum 3000 words';

// Embed
$string['embed_enabled'] = 'Ouvert par défaut';
$string['embed_enabled_help'] = 'Sélectionnez Oui si vous souhaitez que le chatbot soit ouvert par défaut.';
$string['embed_position'] = 'Position du bot sur la page';
$string['embed_position_help'] = 'Sélectionnez la position du bot sur la page';
$string['bottom_left'] = 'En bas à gauche';
$string['bottom_right'] = 'En bas à droite';
$string['top_left'] = 'En haut à gauche';
$string['top_right'] = 'En haut à droite';

// Tasks
$string['update_url_content'] = 'Mettre à jour le contenu de l’URL';

// Small talk settings
$string['small_talk_json'] = '[
    {
        "name": "Petite conversation - Bonjour",
        "value": "Bonjour",
        "answer": "Bonjour! Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Salut"
            },
            {
                "value": "Hey"
            },
            {
                "value": "Salutations"
            },
            {
                "value": "Bonjour"
            },
            {
                "value": "Bon après-midi"
            },
            {
                "value": "Bonsoir"
            }
        ]
    },
    {
        "name": "Petite conversation - Comment ça va?",
        "value": "Comment ça va?",
        "answer": "Je suis un assistant IA. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Comment ça va?"
            },
            {
                "value": "Comment allez-vous?"
            },
            {
                "value": "Comment ça se passe?"
            },
            {
                "value": "Comment vous sentez-vous?"
            },
            {
                "value": "Comment allez-vous aujourd\'hui?"
            }
        ]
    },
    {
        "name": "Petite conversation - Au revoir",
        "value": "Au revoir",
        "answer": "Au revoir! Passez une excellente journée! Si vous avez besoin d\'aide, n\'hésitez pas à demander.",
        "examples": [
            {
                "value": "Bye"
            },
            {
                "value": "Adieu"
            },
            {
                "value": "À plus tard"
            },
            {
                "value": "À bientôt"
            },
            {
                "value": "Prenez soin de vous"
            }
        ]
    },
    {
        "name": "Petite conversation - Merci",
        "value": "Merci",
        "answer": "De rien! Si vous avez besoin d\'aide, n\'hésitez pas à demander.",
        "examples": [
            {
                "value": "Merci"
            },
            {
                "value": "Merci beaucoup"
            },
            {
                "value": "Merci infiniment"
            },
            {
                "value": "Gracias"
            },
            {
                "value": "Merci"
            }
        ]
    },
    {
        "name": "Petite conversation - Qui êtes-vous?",
        "value": "Qui êtes-vous?",
        "answer": "Je suis un assistant IA. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Qui êtes-vous?"
            },
            {
                "value": "Qu\'êtes-vous?"
            },
            {
                "value": "Quel est votre nom?"
            },
            {
                "value": "Que faites-vous?"
            },
            {
                "value": "Que pouvez-vous faire?"
            }
        ]
    },
    {
        "name": "Petite conversation - D\'où venez-vous?",
        "value": "D\'où venez-vous?",
        "answer": "Je suis un assistant IA. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "D\'où venez-vous?"
            },
            {
                "value": "D\'où venez-vous?"
            },
            {
                "value": "Où êtes-vous né?"
            },
            {
                "value": "Où habitez-vous?"
            },
            {
                "value": "Où résidez-vous?"
            }
        ]
    },
    {
        "name": "Petite conversation - Quel est votre nom?",
        "value": "Quel est votre nom?",
        "answer": "Mon nom est . Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Quel est votre nom?"
            },
            {
                "value": "Comment vous appelez-vous?"
            },
            {
                "value": "Comment devrais-je vous appeler?"
            },
            {
                "value": "Comment vous appelle-t-on?"
            },
            {
                "value": "Quel est votre titre?"
            }
        ]
    },
    {
        "name": "Petite conversation - Que faites-vous?",
        "value": "Que faites-vous?",
        "answer": "Je suis un assistant IA. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Que faites-vous?"
            },
            {
                "value": "Quel est votre travail?"
            },
            {
                "value": "Quel est votre rôle?"
            },
            {
                "value": "Quelle est votre fonction?"
            },
            {
                "value": "Quel est votre but?"
            }
        ]
    },
    {
        "name": "Petite conversation - Que pouvez-vous faire?",
        "value": "Que pouvez-vous faire?",
        "answer": "Je suis un assistant IA. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Que pouvez-vous faire?"
            },
            {
                "value": "Quelles sont vos capacités?"
            },
            {
                "value": "Quelles sont vos fonctions?"
            },
            {
                "value": "Quelles sont vos caractéristiques?"
            },
            {
                "value": "Quelles sont vos compétences?"
            }
        ]
    },
    {
        "name": "Petite conversation - Que faites-vous?",
        "value": "Que faites-vous?",
        "answer": "Je suis un assistant IA. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Que faites-vous?"
            },
            {
                "value": "Que faites-vous?"
            },
            {
                "value": "Sur quoi travaillez-vous?"
            },
            {
                "value": "Avec quoi êtes-vous occupé?"
            },
            {
                "value": "Avec quoi êtes-vous occupé?"
            }
        ]
    },
    {
        "name": "Petite conversation - Êtes-vous stupide?",
        "value": "Êtes-vous stupide?",
        "answer": "Je suis un assistant IA formé sur des informations spécifiques basées sur le programme fourni par l\'instructeur. Comment puis-je vous aider aujourd\'hui?",
        "examples": [
            {
                "value": "Hé imbécile!"
            },
            {
                "value": "Êtes-vous stupide?"
            },
            {
                "value": "Êtes-vous intelligent?"
            },
            {
                "value": "Êtes-vous intelligent?"
            },
            {
                "value": "Êtes-vous intelligent?"
            }
        ]
    }
]
';

