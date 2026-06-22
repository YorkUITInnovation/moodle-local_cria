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


$string['about'] = 'About';
$string['actions'] = 'Actions';
$string['add'] = 'Add';
$string['add_bot'] = 'Create new bot';
$string['add_content'] = 'Add content';
$string['add_document'] = 'Add document';
$string['add_entity'] = 'Add entity';
$string['add_intent'] = 'Add intent';
$string['add_keyword'] = 'Add keyword';
$string['add_model'] = 'Add model';
$string['add_provider'] = 'Add provider';
$string['api_base_url'] = 'API base URL';
$string['api_base_url_help'] = 'The base URL for the provider API (e.g. https://api.openai.com/v1).';
$string['api_key'] = 'API key';
$string['api_model'] = 'API model';
$string['add_question'] = 'Add question';
$string['add_start_up_question'] = 'Add Start-up Question';
$string['add_type'] = 'Add type';
$string['available_child'] = 'Make this bot available to other bots';
$string['available_child_help'] = 'Select Yes if you want this bot to be available to other bots as a child bot. This is useful if you want to create a bot that uses the content of another bot.';
$string['advanced_settings'] = 'Advanced settings';
$string['all'] = 'All';
$string['analytics_dashboard'] = 'Analytics Dashboard';
$string['answer'] = 'Answer';
$string['audience'] = 'Audience';
$string['auto_test'] = 'Auto-Test';
$string['automated_tasks'] = 'Automated tasks';
$string['assign_users'] = 'Assign users';
$string['ask_a_question'] = 'Ask a question';
$string['azure_api_version'] = 'Azure OpenAI api version';
$string['azure_bot_settings'] = 'Azure Bot Settings';
$string['azure_deployment_name'] = 'Azure Deployment Name';
$string['azure_endpoint'] = 'Azure Endpoint';
$string['azure_endpoint_help'] = 'URL of the Azure endpoint';
$string['azure_endpoint_or_resource'] = 'Endpoint URL or resource';
$string['azure_endpoint_or_resource_help'] = 'Use a full endpoint URL when required by the provider/model family (for example, Azure AI Foundry style endpoints). Legacy resource values are still supported for backward compatibility.';
$string['azure_key'] = 'Azure Key';
$string['azure_key_help'] = 'Use one of the two keys available for the OpenAI service in Azure';
$string['bot'] = 'Bot';
$string['bot_already_exists'] = 'A bot with this id already exists.';
$string['bot_api_key'] = 'API Key';
$string['bot_api_key_instructions'] = 'The API Key and bot name is meant to be used with CriaBot directly. This is only needed if you want to build your own AI integration into another software application.' .
 ' If you need an API Key for Cria, which allows direct calls to LLMs, please reach out to the administrator. If you want to embed this bot on a web page, click on the "Share" button for this bot on the dashboard, and copy the embed code.' .
 ' <br><br><b>DO NOT MODIFY THESE VALUES. YOU WILL BREAK YOUR EXISTING BOT!</b>';
$string['bot_api_key_help'] = 'The API Key is meant to be used with CriaBot Directly and not Cria itself. If you need an API Key for Cria, please reachout to the administrator.';
$string['bot_configuration'] = 'BotCraft';
$string['bot_configuration_help'] = 'Easily create a bot by providing your own documentation and system messages.';
$string['bot_configurations'] = 'Bot configurations';
$string['bot_contact'] = 'Contact';
$string['bot_contact_help'] = 'Enter the email address and/or phone number of the person who should be contacted if there is an issue with the bot.';
$string['bot_help_text'] = 'Bot help text';
$string['bot_help_text_help'] = 'Enter a short message. Example: Ask SAVY! This message will be displayed when users hover over the bot icon. It is also used for screen readers to be WCAG compliant.';
$string['bot_locale'] = 'Text-to-Speech language';
$string['bot_locale_help'] = 'Select the language you would like the bot to speak in.';
$string['bot_log_details'] = 'Log details';
$string['bot_models'] = 'Models';
$string['bot_name'] = 'Bot name (Used with CriaBot)';
$string['bot_personality'] = 'Bot personality';
$string['bot_trust_warning'] = 'Bot trust warning';
$string['bot_trust_warning_help'] = 'Enable this setting to display a warning message to the user that the bot is not a human.';
$string['bot_type'] = 'Bot type';
$string['bot_types'] = 'Bot types';
$string['bot_type_factual'] = 'Factual';
$string['bot_type_transcription'] = 'Transcription';
$string['bot_type_help'] = 'The bot type sets the bot\'s personality and the type of content it can process.';
$string['bot_system_message'] = 'System message';
$string['bot_system_message_help'] = 'Enter a prompt that describes what your bot does. This message is usually designed to be informative, contextually relevant, and contribute to a more natural and coherent dialogue between the user and the model.';
$string['bot_watermark'] = 'Display Cria Watermark';
$string['bot_watermark_help'] = 'Select Yes if you want the Cria watermark to be displayed on the imbedded bot.';
$string['bot_content_training_framework'] = 'Content training framework';
$string['bots'] = 'Bots';
$string['cachedef_cria_system_messages'] = 'Caches all system messages for bots';
$string['cancel'] = 'Cancel';
$string['chatbot_framework'] = 'Chatbot framework';
$string['chat_does_not_exist'] = 'The chat requested does not exist.';
$string['child_bots'] = 'Child bots';
$string['child_bots_help'] = 'Select the bots you want to make available to this bot. This is useful if you want to create a bot that uses the content of another bot.';
$string['chunk_limit'] = 'Number of words per chunk';
$string['chunk_limit_help'] = 'OpenAI works on chunks of text. This setting defines the number of words per chunk.<br>
For GPT-3.5-turbo 16k, the maximum number of words per chunk is 12000.';
$string['close'] = 'Close';
$string['cohere_api_key'] = 'Cohere API key';
$string['cohere_api_key_help'] = 'Cohere API key Required for ranking & reranking to work. Without this key, you will never be able to prevent the AI from generating answers.';
$string['column_name_must_exist'] = 'Column <b>${a}</b> missing. It must exist in the file you are importing.';
$string['compare_text_bot_id'] = 'Compare text bot id';
$string['compare_text_bot_id_help'] = 'Enter the bot id to use for the compare text requirement when using non-indexed bots.';
$string['completion_cost'] = 'Completion cost';
$string['completion_tokens'] = 'Completion tokens';
$string['content'] = 'Content';
$string['content_for'] = 'Content for';
$string['context'] = 'Context';
$string['cost'] = 'Cost';
$string['conversation_styles'] = 'Conversation styles';
$string['create_example_questions'] = 'Have AI create 5 example questions?';
$string['create_meeting_notes'] = 'MinutesMaster';
$string['create_meeting_notes_help'] = 'Use this tool to create meeting notes based on a transcription';
$string['cria_suite'] = 'Cria Suite';
$string['criadex_model_id'] = 'Criax model id';
$string['date_range'] = 'Date range';
$string['debugging'] = 'Debugging';
$string['default_disclaimer_text'] = "The answer is provided by an AI and may not be accurate.";
$string['default_no_context_message'] = "I'm sorry, I don't have an answer for that question. Please try asking another question.";
$string['default_user_prompt'] = 'Default user prompt';
$string['default_user_prompt_help'] = 'If your but has a default prompt, enter it here. If the requires user prompt ' .
    ' above is set to Yes, this prompt will prepended to the user propmt. Note it is not visible on the page.';
$string['deployment_name'] = 'Deployment name';
$string['deployment_name_help'] = 'The model deployment name in Azure';
$string['delete'] = 'Delete';
$string['delete_all'] = 'Delete all';
$string['delete_document_confirmation'] = 'Are you sure you want to delete the selected document(s)?';
$string['delete_entity_help'] = 'Are you sure you want to delete this entity?';
$string['delete_keyword_help'] = 'Are you sure you want to delete this keyword?';
$string['delete_selected'] = 'Delete Selected';
$string['description'] = 'Description';
$string['details'] = 'Details';
$string['development'] = 'Development';
$string['display_settings'] = 'Display setttings';
$string['documents'] = 'Documents';
$string['download'] = 'Download';
$string['edit'] = 'Edit';
$string['edit_bot'] = 'Edit bot';
$string['edit_content'] = 'Edit content';
$string['edit_entity'] = 'Edit entity';
$string['edit_intent'] = 'Edit intent';
$string['edit_keyword'] = 'Edit keyword';
$string['edit_question'] = 'Edit question';
$string['embed_code'] = 'Embed Code';
$string['embed_code_help'] = 'Copy and paste the code below onto you webpages to embed the chatbot.';
$string['embedding'] = 'Embedding';
$string['embedding_server_url'] = 'Embedding server URL';
$string['embedding_server_url_help'] = 'URL of the embedding server';
$string['entities'] = 'Entities';
$string['entity'] = 'Entity';
$string['error_importfile'] = 'There was an error importing the file';
$string['error_message'] = 'Error message';
$string['event_file_created'] = 'Cria Event: File created';
$string['examples'] = 'Examples';
$string['existing_bots'] = 'Existing bots';
$string['existing_bot_models'] = 'Existing models';
$string['existing_bot_types'] = 'Existing bot types';
$string['export_questions'] = 'Export questions';
$string['faculties'] = 'Faculties';
$string['faculties_help'] = 'One line per faculty. Each line must be in the following format:<br>' .
    '<b>Faculty acronymn</b> pipe (|) <b>Faculty name</b> <br><br>' .
    'Example:<br>' .
    'ED|Faculty of Education';
$string['faculty'] = 'Faculty';
$string['file'] = 'File';
$string['files'] = 'Files';
$string['fine_tuning'] = 'Fine-tuning';
$string['fine_tuning_help'] = 'Fine-tuning will provide you with extra parameters to fine-tune your model. It will also' .
    ' allow you to separate your content into categories making your bot more precise and accurate.' .
    ' This is an advanced feature and should only be used if you know what you are doing.';
$string['for_developers'] = 'For developers';
$string['full_logs'] = 'Full logs';
$string['generate_answer'] = 'Let AI generate an answer based on your answer above?';
$string['generate_answer_help'] = 'If yes, the AI will generate an answer based on the answer you provided above.'
. ' If no, the answer above will be provided. NOTE: if you are using images, maps, videos or other media, you should always select no.';
$string['generate_synonyms'] = 'Let AI generate synonyms for this keyword?';
$string['generate_synonyms_help'] = 'If yes is selected, AI will try it\'s best to generate some synonyms for this keyword.';
$string['gpt_cost'] = 'GPT cost?';
$string['gpt_cost_help'] = 'Cost of GPT based per 1000 tokens';
$string['groups'] = 'Groups';
$string['icon_url'] = 'Icon URL';
$string['import_questions'] = 'Import questions';
$string['index_context'] = 'Index context';
$string['index_files'] = 'Index files';
$string['indexing_failed'] = 'Error';
$string['indexing_pending'] = 'Pending';
$string['indexing_server_api_key'] = 'Indexing server API Key';
$string['indexing_server_api_key_help'] = 'Enter the API key for the indexing server';
$string['indexing_server_url'] = 'Indexing server URL';
$string['indexing_server_url_help'] = 'URL of the indexing server';
$string['indexing_started'] = 'Training';
$string['indexing_success'] = 'Trained';
$string['ip'] = 'IP';
$string['is_embedding'] = 'This is an embedding model';
$string['integrations_disclaimer_text'] = 'Disclaimer text';
$string['integrations_disclaimer_text_help'] = 'Enter the disclaimer text for the bot. The disclaimer text will be shown in the embedded bot.';
$string['integrations_first_email_only'] = 'Reply to first email only';
$string['integrations_first_email_only_help'] = 'Select Yes if you want the bot to only reply to the first email. This is useful for email bots.';
$string['integrations_no_context_reply'] = 'No context reply enabled for Email and Teams bots';
$string['integrations_no_context_reply_help'] = 'Select Yes if you want the bot to use historical context when replying to emails and Teams messages. This is especially important for Teams and if first email only is set to no.';
$string['intent'] = 'Intent';
$string['intents'] = 'Intents';
$string['keyword'] = 'Keyword';
$string['keywords'] = 'Keywords';
$string['label'] = 'Display Label';
$string['languages'] = 'Languages';
$string['languages_help'] = 'One line per language. Each line must be in the following format:<br>' .
    '<b>Language code</b> pipe (|) <b>Language name</b> <br><br>' .
    'Example:<br>' .
    'en|English';
$string['llm_generate_related_prompts'] = 'LLM generate related prompts';
$string['llm_generate_related_prompts_help'] = 'Select Yes if you want the LLM to generate related prompts. If you have defined related prompts, this will be ignored.';
$string['llm_models'] = 'LLM models';
$string['log_filter_settings'] = 'Log filter settings';
$string['logs'] = 'Logs';
$string['logs_for'] = 'Logs for';
$string['long'] = 'Long';
$string['medium'] = 'Medium';
$string['message'] = 'Message';
$string['model'] = 'Model';
$string['model_max_tokens'] = 'Model max tokens';
$string['model_max_tokens_help'] = 'Maximum number of tokens this model can generate.' .
    '<br> For GPT-3.5-turbo 4k: 4096.' .
    '<br> For GPT-3.5-turbo 16k: 16384.' .
    '<br> For GPT-4: 8192.' .
    '<br> For GPT-4-32k: 32768.';
$string['model_name'] = 'Model name';
$string['ms_app_id'] = 'Microsoft App ID';
$string['ms_app_id_help'] = 'Enter the Microsoft App ID';
$string['ms_app_password'] = 'Microsoft App Password';
$string['ms_app_password_help'] = 'Enter the Microsoft App Password';
$string['name'] = 'Name';
$string['new_category'] = 'New category';
$string['new_role'] = 'New role';
$string['no_context_email_message'] = '<p>The bot {$a->bot_name} was unable to return an answer for question: {$a->prompt}.</p>';
$string['no_context_email_message_llm_guess'] = '<p>However, because the LLM guess feature is enabled, it did return '
 . 'this answer:</p><p>{$a->answer}</p>';
$string['no_context_message'] = 'No results message';
$string['no_context_subject'] = 'No results returned by bot';
$string['no_context_use_message'] = 'Use no context message';
$string['no_context_use_message_help'] = 'Select Yes if you want to use the no context message. If you select No, ' .
    ' the bot will always return a reply. Note: This could lead to hallucinations and false information.';
$string['no_context_email'] = 'No context notification email';
$string['no_context_email_help'] = 'Enter the email address to receive notifications when the bot has no reply (context).';
$string['no_context_llm_guess'] = 'Use LLM guess';
$string['no_context_llm_guess_help'] = 'Select Yes if you want the bot to guess at an answer.';
$string['nodes'] = 'Nodes';
$string['other'] = 'Other';
$string['parse_strategy'] = 'Preprocess strategy';
$string['parse_strategy_help'] = 'Select the type of preprocessing you would like to use.';
$string['paste_text'] = 'Paste your text here';
$string['payload'] = 'Payload';
$string['permissions'] = 'Permissions';
$string['plugin_path'] = 'Plugin path';
$string['pluginname'] = 'Cria';
$string['preprocess_rules'] = 'Prompt preprocessing rules';
$string['preprocess_rules_help'] = 'Enter the rules you would like to use for preprocessing the prompt. '
    . 'To use the variables above, make sure you put the variable sin square brackets ([name]) '
    . 'One rule per line.<br>'
    . 'For example, if your payload contains a variable called name, you can use the following rule:<br>'
    . 'My name is [name]. <br>'
    . 'This will replace [name] with the value of the variable name.<br><br>'
    . 'If you want to replace a sentence, you can use the following format:<br>'
    . 'What is this course about => Can you descriibe this course?'
    . '<b>Note</b>: Do not add too many rules as each rule increases the size of the prompt, therefore increasing the number of tokens.'
    . 'This will add costs to each call and potential make some calls fail if too many tokens are used.';
$string['privacy:metadata'] = 'This plugin stores no personal data.';
$string['process'] = 'Process';
$string['program'] = 'Program';
$string['programs'] = 'Programs';
$string['programs_help'] = 'One line per program. Each line must be in the following format:<br>' .
    '<b>Program acronymn</b> pipe (|) <b>Program name</b> <br><br>' .
    'Example:<br>' .
    'BEd|Bachelor of Education';
$string['prompt'] = 'Prompt';
$string['prompt_cost'] = 'Prompt cost';
$string['prompt_settings'] = 'Prompt settings';
$string['prompt_tokens'] = 'Prompt tokens';
$string['provider'] = 'Provider';
$string['provider_image'] = 'Provider image';
$string['provider_type'] = 'Provider type';
$string['provider_type_help'] = 'The backend API type used for routing requests to Criadex. This determines which API format is used for model operations. Currently supported: Azure OpenAI, Cohere, Ragflow, and other generic providers.';
$string['model_type'] = 'Model type';
$string['model_type_chat'] = 'Chat';
$string['model_type_embedding'] = 'Embedding';
$string['model_type_rerank'] = 'Rerank';
$string['providers'] = 'Providers';
$string['sync_status'] = 'Sync status';
$string['sync_error_unknown'] = 'Unknown API error';
$string['sync_models_failed'] = 'Could not sync models from Criadex. Check Criadex URL and API key in plugin settings.';
$string['sync_models_empty'] = 'Criadex returned no models to sync.';
$string['sync_models_success'] = 'Model sync complete. Created: {$a->created}, updated: {$a->updated}, skipped: {$a->skipped}, removed: {$a->removed}.';
$string['sync_dedupe_failed'] = 'Could not deduplicate models in Criadex.';
$string['sync_dedupe_success'] = 'Model deduplication complete. Removed {$a->remote} duplicate Criadex model(s) and {$a->local} duplicate Moodle model row(s).';
$string['sync_bot_missing_models'] = 'Bot save succeeded locally but could not push to Criabot: LLM or embedding model is not linked to Criadex.';
$string['sync_bot_push_failed'] = 'Failed to push bot configuration to Criabot.';
$string['sync_criabot_unreachable'] = 'Could not reach Criabot. Check Criabot URL in plugin settings.';
$string['sync_check_criadex_url'] = 'Criadex URL configured';
$string['sync_check_criabot_url'] = 'Criabot URL configured';
$string['sync_check_api_key'] = 'Criadex API key configured';
$string['sync_check_criadex_models'] = 'Criadex model registry reachable';
$string['sync_check_local_models'] = 'All local models linked to Criadex';
$string['sync_check_local_models_detail'] = 'Linked: {$a->linked}, unlinked: {$a->unlinked}, remote: {$a->remote}, usable: {$a->usable}';
$string['sync_check_bots_models'] = 'All bots have valid model links';
$string['sync_check_bots_models_detail'] = '{$a} bot(s) missing Criadex model links';
$string['sync_check_criabot'] = 'Criabot API reachable';
$string['sync_check_criabot_ok'] = 'Criabot responded to health check';
$string['sync_check_ragflow'] = 'Ragflow API reachable';
$string['sync_check_ragflow_ok'] = 'Ragflow health check passed';
$string['sync_check_ragflow_http'] = 'Ragflow returned HTTP {$a}';
$string['sync_check_ragflow_credentials'] = 'Ragflow API key and tenant configured';
$string['sync_check_ragflow_credentials_ok'] = 'API key accepted by Ragflow. Tenant id {$a->tenant} matches plugin settings (must also match Criadex/Criabot RAGFLOW_TENANT_ID).';
$string['sync_check_ragflow_credentials_skipped'] = 'Optional: add Ragflow API key and tenant id in plugin settings to validate credentials on this page.';
$string['sync_check_ragflow_api_not_configured'] = 'Ragflow API key is not set in plugin settings.';
$string['sync_check_ragflow_api_ok'] = 'Ragflow accepted the configured API key.';
$string['sync_check_ragflow_api_invalid'] = 'Ragflow rejected the API key (HTTP {$a}).';
$string['sync_check_ragflow_api_http'] = 'Unexpected Ragflow API response (HTTP {$a}).';
$string['sync_check_ragflow_tenant_not_configured'] = 'Ragflow tenant id is not set in plugin settings.';
$string['sync_check_ragflow_tenant_missing'] = 'Ragflow tenant id is required when an API key is configured.';
$string['sync_check_ragflow_tenant_invalid'] = 'Ragflow tenant id must be a 32-character hex string.';
$string['sync_check_ragflow_tenant_ok'] = 'Tenant id configured: {$a}';
$string['sync_check_unreachable'] = 'Service unreachable or returned an invalid response';
$string['sync_check_label'] = 'Check';
$string['sync_check_status'] = 'Status';
$string['sync_check_detail'] = 'Detail';
$string['sync_status_ok'] = 'OK';
$string['sync_status_issue'] = 'Issue';
$string['sync_overall_healthy'] = 'Cria, Criadex, and Criabot appear to be in sync.';
$string['sync_overall_issues'] = 'One or more sync checks failed. Review the details below.';
$string['sync_last_model_sync'] = 'Last model sync';
$string['sync_action_pull_models'] = 'Pull models from Criadex';
$string['sync_action_reconcile_models'] = 'Reconcile models (pull + remove stale)';
$string['sync_action_dedupe_models'] = 'Deduplicate models';
$string['sync_action_repush_bots'] = 'Re-push all bots to Criabot';
$string['sync_repush_bots_success'] = 'Re-pushed {$a} bot configuration(s) to Criabot.';
$string['sync_repush_bots_partial'] = 'Re-pushed {$a->pushed} bot(s) to Criabot. {$a->failed} failed, {$a->skipped} skipped.';
$string['sync_repush_bots_none'] = 'No bot configurations were re-pushed. Check model links, published intents, and Criabot connectivity on Sync status.';
$string['sync_repush_failed_bot'] = 'Criabot push failed for bot {$a->name}';
$string['sync_repush_skipped_models'] = 'Skipped bot {$a->bot}: models are not linked to Criadex';
$string['sync_repush_skipped_no_intents'] = 'Skipped bot {$a->bot}: no published intents';
$string['sync_repush_more_failures'] = '…and {$a} more re-push issue(s). See server logs with debugging enabled.';
$string['sync_content_push_failed'] = 'Failed to publish content to Criabot.';
$string['sync_content_delete_failed'] = 'Failed to delete content from Criabot (local copy was removed).';
$string['sync_index_upload_failed'] = 'Failed to upload parsed document to Criabot.';
$string['sync_auto_help'] = 'Creating or editing a model pushes to Criadex automatically. Creating or editing a bot (and publishing intents/content) pushes to Criabot automatically. Use the actions below only to pull remote models, remove duplicates, or repair drift.';
$string['sync_ragflow_ui_note'] = 'Ragflow tenant models (for example GPT-5.4) are synced automatically into Criadex and then into this Models list when you open the page or pull models from Sync status. Ensure Ragflow URL, API key, and tenant id match Criadex/Criabot docker.env (RAGFLOW_TENANT_ID).';
$string['task_sync_models'] = 'Sync Cria models from Criadex';
$string['never'] = 'Never';
$string['sync_check_provider_types'] = 'Local providers cover remote model types';
$string['sync_check_provider_types_ok'] = 'All remote provider types have a matching local provider.';
$string['sync_check_provider_types_missing'] = 'Missing local providers for types: {$a}';
$string['sync_check_criabot_bots'] = 'Moodle bots exist on Criabot';
$string['sync_check_criabot_bots_ok'] = 'All published bots are registered on Criabot.';
$string['sync_check_criabot_bots_missing'] = 'Missing on Criabot: {$a}';
$string['sync_missing_bot_type'] = '{$a} (missing bot type)';
$string['sync_check_bot_type_assigned'] = 'All bots have a bot type';
$string['sync_check_bot_type_assigned_ok'] = 'Every bot has a bot type selected.';
$string['sync_check_bot_type_assigned_missing'] = 'Missing bot type for bot id(s): {$a}';
$string['sync_check_bot_types'] = 'Bot types configured';
$string['sync_check_bot_types_detail'] = '{$a} bot type(s) in Moodle';
$string['sync_check_solution'] = 'Suggested fix';
$string['sync_action_open_settings'] = 'Open plugin settings';
$string['sync_action_open_models'] = 'Manage models';
$string['sync_action_cleanup_unlinked'] = 'Remove unused unlinked models';
$string['sync_action_auto_repair'] = 'Repair common issues';
$string['sync_action_manage_bot_types'] = 'Manage bot types';
$string['sync_bot_edit_link'] = 'Edit bot {$a}';
$string['sync_solution_settings'] = 'Set Criadex URL, Criabot URL, and Criadex API key in plugin settings. Use the Docker service hostname when Cria runs in the same compose stack (for example http://criadex:25574).';
$string['sync_solution_service_unreachable'] = 'Verify the service is running, the URL in plugin settings is correct, and the Cria container can reach it on the Docker network.';
$string['sync_solution_unlinked_models'] = 'Pull models from Criadex first. Then remove duplicate local rows that have no Criadex id and are not used by any bot.';
$string['sync_solution_unlinked_models_list'] = 'Unlinked models: {$a}.';
$string['sync_solution_bots_model_links'] = 'Open each affected bot and choose LLM/embedding models that are linked to Criadex, or sync models and remap bots to the linked copies.';
$string['sync_solution_missing_criabot_bots'] = 'Re-push bots to Criabot. If that fails, confirm the bot has a bot type, linked models, and published intents (for intent-based bots).';
$string['sync_solution_missing_bot_type'] = 'Open each bot below and select a bot type (Factual, GenAI, etc.). Bots without a type cannot sync to Criabot correctly.';
$string['sync_solution_provider_types'] = 'Pull models from Criadex to auto-create missing local provider rows for remote model types.';
$string['sync_solution_no_bot_types'] = 'Create at least one bot type before creating bots.';
$string['sync_solution_ragflow_credentials'] = 'In plugin settings, set Ragflow URL, API key (from Ragflow UI), and tenant id (from api_token query). The same API key and tenant id must be in Criadex/Criabot docker.env.';
$string['sync_solution_generic'] = 'Review the detail column and use the repair actions on this page. Sync status: {$a}';
$string['sync_cleanup_unlinked_success'] = 'Removed {$a->removed} unused unlinked model row(s). {$a->blocked} still in use or could not be auto-removed.';
$string['sync_cleanup_unlinked_blocked_detail'] = 'These unlinked models are still referenced by bots and must be remapped manually: {$a}';
$string['sync_local_dedupe_removed'] = 'Removed {$a} duplicate local model row(s).';
$string['publish'] = 'Publish';
$string['publish_all'] = 'Publish all files';
$string['publish_document_confirm'] = 'Are you sure you want to publish the selected document(s)?';
$string['publish_questions'] = 'Publish questions';
$string['publish_questions_confirmation'] = 'Are you sure you want to publish the selected question(s)?';
$string['question'] = 'Question';
$string['questions'] = 'Questions';
$string['question_for'] = 'Questions for';
$string['related_questions'] = 'Related Questions';
$string['related_questions_help'] = '<p>Related Questions are follow-up questions that are related to the main question. ' .
    'These questions are used to help quide the requester to the correct answer. The field will only capture the first three questions</p>' .
    '<p>Enter one question per line. Each question should be in the following format:</p>' .
    '<b>Label</b> pipe (|) <b>Question (prompt)</b> <br><br>' .
    'Example:<br>' .
    'Capital of Canada!|What is the capital of Canada?';
$string['related_prompts'] = 'Start-up questions';
$string['related_prompts_help'] = 'You can add up to 6 start up questions/prompts. These questions will be displayed to the user when the bot is first started.'
. 'The questions must be in the follwoing JSON format:<br>'
. '<pre>'
. '[{"label":"a display name", "prompt":"the question prompt"},{"label":"nother display name", "prompt":"another question prompt"}]'
. '</pre>';
$string['requires_content_prompt'] = 'Requires content prompt';
$string['requires_content_prompt_help'] = 'Select Yes if you want a text area to paste content that can be used with a user prompt';
$string['requires_user_prompt'] = 'Requires user prompt';
$string['requires_user_prompt_help'] = 'Select Yes if you want a text area to enter a user prompt. If you select No,' .
    ' make sure you set a default user prompt below.';
$string['rerank_model_id'] = 'Rerank model';
$string['response'] = 'Response';
$string['response_length'] = 'Response length';
$string['response_size'] = 'Response size (in tokens)';
$string['retrieved_from'] = 'Retrieved from';
$string['return'] = 'Return';
$string['role'] = 'Role';
$string['role_description'] = 'Role description';
$string['role_name'] = 'Role name';
$string['role_permissions'] = 'Role permissions';
$string['role_shortname'] = 'Role short name';
$string['save'] = 'Save';
$string['save_and_publish'] = 'Save and publish';
$string['select'] = 'Select';
$string['select_a_provider'] = 'Select a provider to continue.';
$string['share'] = 'Share';
$string['short'] = 'Short';
$string['submit'] = 'Submit';
$string['subtitle'] = 'Subtitle';
$string['statistics'] = 'Statistics';
$string['status'] = 'Status';
$string['support_email'] = 'Support email';
$string['support_email_help'] = 'Enter the email address of a person who shoudl be notified when an error is encountered during a chat.';
$string['synonym'] = 'Synonym';
$string['synonyms'] = 'Synonyms';
$string['system_message'] = 'System message';
$string['system_reserved'] = 'System reserved';
$string['take_me_there'] = 'Let\'s go!';
$string['test_bot'] = 'Test bot';
$string['theme_color'] = 'Theme color';
$string['testing_bot'] = 'Testing bot';
$string['title'] = 'Title';
$string['tone'] = 'Tone';
$string['topic_keywords'] = 'Keywords <button class="btn btn-secondary btn-sm mr-1" id="build-keywords-array" type="button">Build keywords array</button>';
$string['topic_keywords_help'] = 'Enter the keywords you would like to use to filter the logs. Must be in json format. Example:<br>'
. '{
    "technical": ["API", "integration", "bug", "error"],
    "business": ["pricing", "features", "upgrade"],
    "support": ["help", "assistance", "question"]
  }';
$string['topic_options'] = 'Topic Menu Options <button class="btn btn-secondary btn-sm mr-1" id="build-options-array" type="button">Build Options Menu Array</button>';
$string['topic_options_help'] = 'Enter the options you would like to use to filter the logs. Must be in json format. Example:<br>'
    . '[
    {"value": "all", "label": "All"},
    {"value": "technical", "label": "Technical"},
    {"value": "business", "label": "Business"},
    {"value": "support", "label": "Support"}
    ]';
$string['total_tokens'] = 'Total tokens';
$string['total_usage_cost'] = 'Total usage cost';
$string['total_words'] = 'Number of words in combined content:';
$string['update'] = 'Update';
$string['timecreated'] = 'Time created';
$string['translate'] = 'Translate';
$string['upload_files'] = 'Upload Files';
$string['use_bot_server'] = 'Requires uploading documents?';
$string['use_bot_server_help'] = 'If you this type rqeuires uploading documents, select Yes.';
$string['user_prompt'] = 'User prompt';
$string['userid'] = 'User id';
$string['variables'] = 'Variables';
$string['variables_help'] = 'If you are sending a payload, add the variables you would like to receive. The variables will be used with the prompt preprocessing. Add one variable per line' .
    'Example:' .
    'name<br>' .
    'idnumber';
$string['view_parsing_data'] = 'View parsing data';
$string['web_page_help'] = 'Enter web page addresses including http/https. Separate each address with a new line.<br>'
 . '<br>Please note that some web pages may not permit their content to be captured. '
 . 'If you encounter a \'no content\' message while testing your bot, consider downloading the generated document to '
 . 'check the content.';
$string['web_pages'] = 'Web Pages';
$string['welcome_message'] = 'Welcome message';
$string['welcome_message_help'] = 'The welcome message to be displayed when the bot is used';
$string['word_count'] = 'Word count';
$string['web_search_enabled'] = 'Enable web search fallback for this bot';
$string['web_search_enabled_help'] = 'When enabled, this bot may use web search fallback when retrieval confidence is low.';

// GPT Settings
$string['max_tokens'] = 'Max tokens';
$string['max_tokens_help'] = 'The maximum number of tokens to generate. Maximum depends on the Chatbot Framework selected.' .
    ' When selecting a Chatbot framework, the maximum number of tokens will be displayed.' .
    ' You should avoid using the maximum number of tokens as this will increase the cost.' .
    ' The answers may also be too long. If you find they are too long, reduce the maximun tokens.';
$string['min_k'] = 'Min K';
$string['temperature'] = 'Temperature';
$string['temperature_help'] = 'The higher the temperature, the crazier the text. Recommend experimenting with values between 0.1 and 1.2.';
$string['top_p'] = 'Top P';
$string['top_p_help'] = 'An alternative to "Top K" sampling, this will stop the completion when the cumulative probability of the tokens generated exceeds the value.';
$string['top_k'] = 'Top K';
$string['top_n'] = 'Top N';
$string['top_k_help'] = 'An alternative to sampling with temperature, called "nucleus sampling", where the model considers the results of the tokens with top_k probability mass. So 0.1 means only the tokens comprising the top 10% probability mass are considered.';
$string['min_relevance'] = 'Minimum relevance';
$string['min_relevance_help'] = 'The minimum relevance of the response. The higher the number, the more relevant the response will be.';
$string['max_context'] = 'Max context';
$string['max_context_help'] = '<b>DO NOT CHANGE THIS VALUE!!!</b><br>' .
    'The maximum number of tokens the bot can handle without throwing an error.' .
    ' This is based on the Chatbot framework and updated automatically when selecting a framework.' .
    ' <p><b>Only change this value if you know exactly what you are doing!</b></p>';

// Capabilites
$string['cria:bot_permissions'] = 'Bot permissions: Grants user the ability to edit permissions for a bot';
$string['cria:delete_bots'] = 'Delete bots: Grants permission to delete bots';
$string['cria:edit_bots'] = 'Add/Edit bots: Grants permission to add/edit bots';
$string['cria:test_bots'] = 'Test bots: Grants permission to test bots';
$string['cria:view_bots'] = 'View bots: Grants permission to view bots';
$string['cria:delete_bot_types'] = 'Delete bot types: Grants permission to delete bot types';
$string['cria:edit_bot_types'] = 'Add/Edit bot types: Grants permission to add/edit bot types';
$string['cria:view_bot_types'] = 'View bot types: Grants permission to view bot types';
$string['cria:delete_bot_content'] = 'Delete bot content: Grants permission to delete bot content';
$string['cria:edit_bot_content'] = 'Add/Edit bot content: Grants permission to add/edit bot content';
$string['cria:view_bot_logs'] = 'View bot logs: Grants permission to view bot logs';
$string['cria:delete_models'] = 'Delete models: Grants permission to delete models';
$string['cria:edit_models'] = 'Add/Edit models: Grants permission to add/edit models';
$string['cria:view_models'] = 'View models: Grants permission to view models';
$string['cria:edit_system_reserved'] = 'Edit system reserved bots: Grants permission to edit system reserved bots';
$string['cria:share_bots'] = 'Share bots: Grants permission to share bots';
$string['cria:translate'] = 'Translate: Grants permission to translate text';
$string['cria:groups'] = 'groups';
$string['cria:view_providers'] = 'groups';
$string['cria:view_advanced_bot_options'] = 'View advanced bot options';
$string['cria:view_conversation_styles'] = 'View conversation styles';

// Error messages
$string['default_error_message'] = 'I\'m not sure what happened, but I\'m unable to answer this question. I have let my administrator know. In the meantime, please ask another question.'; ;
$string['error_message_body'] = 'The following error occurred to bot: {$a->bot_name}<br><br>Prompt: {$a->prompt}<br><br>Error: {
{$a->error_message}';
$string['error_message_subject'] = 'ERROR! Bot failed to respond';
$string['openai_filter'] = 'Oops! It looks like you are trying to use a word that is not allowed. Please rephrase your question.';

// Settings
$string['criabot_url'] = 'CriaBot URL';
$string['criabot_url_help'] = 'Enter the URL for the CriaBot instance you are connecting too.';
$string['criadex_url'] = 'CriaDex URL';
$string['criadex_url_help'] = 'Enter the URL for the CriaDex instance you are connecting too.';
$string['ragflow_url'] = 'Ragflow URL';
$string['ragflow_url_help'] = 'Base URL for Ragflow health and API checks (for example http://ragflow:9380 inside Docker).';
$string['ragflow_api_key'] = 'Ragflow API key';
$string['ragflow_api_key_help'] = 'Optional. Copy from the Ragflow UI. Used by Sync status to verify the key works. Must match RAGFLOW_API_KEY in Criadex/Criabot docker.env.';
$string['ragflow_tenant_id'] = 'Ragflow tenant id';
$string['ragflow_tenant_id_help'] = 'Optional. 32-character tenant id from the Ragflow api_token table. Must match RAGFLOW_TENANT_ID in Criadex/Criabot docker.env.';
$string['criaembed_url'] = 'CriaEmbed URL';
$string['criaembed_url_help'] = 'Enter the URL for the CriaEmbed instance you are connecting too.';
$string['criadex_api_key'] = 'CriaDex API key';
$string['criadex_api_key_help'] = 'Enter the API key for the CriaDex instance you are connecting too. ' .
    'Note: MUST BE A MASTER KEY<br> ';
$string['criaparse_url'] = 'CriaParse URL';
$string['criaparse_url_help'] = 'Enter the URL for the CriaParse instance you are connecting too.';
$string['criascraper_url'] = 'CriaScraper URL';
$string['criascraper_url_help'] = 'Enter the URL for the CriaScraper instance you are connecting too.';
$string['web_search_global_enabled'] = 'Enable web search globally';
$string['web_search_global_enabled_help'] = 'Global switch for web search usage across all bots. Individual bots still need their per-bot web search toggle enabled.';
// MinutesMaster
$string['convert'] = 'Convert';
$string['convertapi_api_key'] = 'ConvertAPI API Key';
$string['convertapi_api_key_help'] = 'You must get a ConverAPI API Key from convertapi_api_key';
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
$string['embed_enabled'] = 'Default open';
$string['embed_enabled_help'] = 'Select Yes if you want the chatbot to be open by default.';
$string['embed_position'] = 'Bot position on page';
$string['embed_position_help'] = 'Select the position of the bot on the page';
$string['bottom_left'] = 'Bottom left';
$string['bottom_right'] = 'Bottom right';
$string['top_left'] = 'Top left';
$string['top_right'] = 'Top right';

// Tasks
$string['update_url_content'] = 'Update URL content';

// Small talk settings
$string['small_talk_json'] = '[
    {
        "name": "Small talk - Hello",
        "value": "Hello",
        "answer": "Hello! How can I help you today?",
        "examples": [
            {
                "value": "Hi"
            },
            {
                "value": "Hey"
            },
            {
                "value": "Greetings"
            },
            {
                "value": "Good morning"
            },
            {
                "value": "Good afternoon"
            },
            {
                "value": "Good evening"
            }
        ]
    },
    {
        "name": "Smalltalk - How are you?",
        "value": "How are you?",
        "answer": "I am an AI assistant. How can I help you today?",
        "examples": [
            {
                "value": "How are you?"
            },
            {
                "value": "How are you doing?"
            },
            {
                "value": "How do you do?"
            },
            {
                "value": "How are you feeling?"
            },
            {
                "value": "How are you today?"
            }
        ]
    },
    {
        "name": "Small talk - Good bye",
        "value": "Good bye",
        "answer": "Good bye! Have a great day! If you need help, feel free to ask.",
        "examples": [
            {
                "value": "Bye"
            },
            {
                "value": "Goodbye"
            },
            {
                "value": "See you later"
            },
            {
                "value": "See you soon"
            },
            {
                "value": "Take care"
            }
        ]
    },
    {
        "name": "Small talk - Thank you",
        "value": "Thank you",
        "answer": "You are welcome! If you need help, feel free to ask.",
        "examples": [
            {
                "value": "Thanks"
            },
            {
                "value": "Thank you very much"
            },
            {
                "value": "Thank you so much"
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
        "name": "Small talk - Who are you?",
        "value": "Who are you?",
        "answer": "I am an AI assistant. How can I help you today?",
        "examples": [
            {
                "value": "Who are you?"
            },
            {
                "value": "What are you?"
            },
            {
                "value": "What is your name?"
            },
            {
                "value": "What do you do?"
            },
            {
                "value": "What can you do?"
            }
        ]
    },
    {
        "name": "Small talk - Where are you from?",
        "value": "Where are you from?",
        "answer": "I am an AI assistant. How can I help you today?",
        "examples": [
            {
                "value": "Where are you from?"
            },
            {
                "value": "Where do you come from?"
            },
            {
                "value": "Where were you born?"
            },
            {
                "value": "Where do you live?"
            },
            {
                "value": "Where do you reside?"
            }
        ]
    },
    {
        "name": "Small talk - What is your name?",
        "value": "What is your name?",
        "answer": "My name is  . How can I help you today?",
        "examples": [
            {
                "value": "What is your name?"
            },
            {
                "value": "What do you call yourself?"
            },
            {
                "value": "What should I call you?"
            },
            {
                "value": "What are you called?"
            },
            {
                "value": "What is your title?"
            }
        ]
    },
    {
        "name": "Small talk - What do you do?",
        "value": "What do you do?",
        "answer": "I am an AI assistant. How can I help you today?",
        "examples": [
            {
                "value": "What do you do?"
            },
            {
                "value": "What is your job?"
            },
            {
                "value": "What is your role?"
            },
            {
                "value": "What is your function?"
            },
            {
                "value": "What is your purpose?"
            }
        ]
    },
    {
        "name": "Small talk - What can you do?",
        "value": "What can you do?",
        "answer": "I am an AI assistant. How can I help you today?",
        "examples": [
            {
                "value": "What can you do?"
            },
            {
                "value": "What are your capabilities?"
            },
            {
                "value": "What are your functions?"
            },
            {
                "value": "What are your features?"
            },
            {
                "value": "What are your abilities?"
            }
        ]
    },
    {
        "name": "Small talk - What are you doing?",
        "value": "What are you doing?",
        "answer": "I am an AI assistant. How can I help you today?",
        "examples": [
            {
                "value": "What are you doing?"
            },
            {
                "value": "What are you up to?"
            },
            {
                "value": "What are you working on?"
            },
            {
                "value": "What are you busy with?"
            },
            {
                "value": "What are you occupied with?"
            }
        ]
    },
    {
        "name": "Small talk - Are you dumb?",
        "value": "Are you dumb?",
        "answer": "I am an AI assistant trained on specific information based on the syllbus the instructor provided. How can I help you today?",
        "examples": [
            {
                "value": "Hey dummy!"
            },
            {
                "value": "Are you stupid?"
            },
            {
                "value": "Are you intelligent?"
            },
            {
                "value": "Are you smart?"
            },
            {
                "value": "Are you clever?"
            }
        ]
    }
]';
