# Cria External API Documentation

This document provides a comprehensive overview of all external API calls available in the Cria system. These APIs are defined in `db/services.php` and implemented in the `classes/external/` folder.

## Table of Contents
1. [Content Management APIs](#content-management-apis)
2. [Bot Management APIs](#bot-management-apis)
3. [Bot Type Management APIs](#bot-type-management-apis)
4. [Bot Role Management APIs](#bot-role-management-apis)
5. [Permission Management APIs](#permission-management-apis)
6. [Chat/Conversation APIs](#chatconversation-apis)
7. [Question Management APIs](#question-management-apis)
8. [Synonym Management APIs](#synonym-management-apis)
9. [Model Management APIs](#model-management-apis)
10. [GPT/AI Response APIs](#gptai-response-apis)
11. [Logging APIs](#logging-apis)
12. [System Configuration APIs](#system-configuration-apis)

---

## Content Management APIs

### 1. cria_content_delete
- **Class**: `local_cria_external_content`
- **Method**: `delete`
- **Type**: Write
- **Description**: Delete content
- **Parameters**: 
  - `id` (int): Content id
- **Returns**: Status code or error message

### 2. cria_content_publish_urls
- **Class**: `local_cria_external_content`
- **Method**: `publish_urls`
- **Type**: Write
- **Description**: Add Web Page
- **Parameters**:
  - `intent_id` (int): Intent id
  - `urls` (string): Web page URLs (newline separated)
- **Returns**: Status code or error message

### 3. cria_content_publish_files
- **Class**: `local_cria_external_content`
- **Method**: `publish_files`
- **Type**: Write
- **Description**: Vectorize all files to indexing server
- **Parameters**:
  - `intent_id` (int): Intent id
- **Returns**: Boolean (true on success)

### 4. cria_content_get_training_status
- **Class**: `local_cria_external_content`
- **Method**: `training_status`
- **Type**: Read
- **Description**: Returns the integer value for training status: 0 = Pending, 3 = Training, 1= Trained, 3 = Error
- **Parameters**:
  - `intent_id` (int): Intent id
- **Returns**: Integer training status

### 5. cria_content_upload
- **Class**: `local_cria_external_content`
- **Method**: `upload_file`
- **Type**: Write
- **Description**: Upload a file to document index
- **Capabilities**: `local/cria:edit_bot_content`
- **Parameters**:
  - `intentid` (int): Intent id
  - `filename` (string): Name of the file
  - `filecontent` (string): Content of the file encoded in base64
  - `parsingstrategy` (string, optional): Parsing strategy
- **Returns**: Upload status

---

## Bot Management APIs

### 6. cria_bot_delete
- **Class**: `local_cria_external_bot`
- **Method**: `delete`
- **Type**: Write
- **Description**: Delete bot
- **Parameters**:
  - `id` (int): Bot id
- **Returns**: Boolean

### 7. cria_get_bot_prompt
- **Class**: `local_cria_external_bot`
- **Method**: `get_prompt`
- **Type**: Read
- **Description**: Get default user prompt for bot
- **Parameters**:
  - `bot_id` (int): Bot id
- **Returns**: Bot prompt string

### 8. cria_create_bot
- **Class**: `local_cria_external_bot`
- **Method**: `create_bot`
- **Type**: Write
- **Description**: Create a new bot
- **Parameters**:
  - `name` (string): Bot name
  - `description` (string): Bot description
  - `bot_type_id` (int): Bot type id
  - `model_id` (int): Model id
  - `max_tokens` (int): Maximum tokens
  - `temperature` (float): Temperature setting
- **Returns**: New bot ID

### 9. cria_get_bot_name
- **Class**: `local_cria_external_bot`
- **Method**: `get_bot_name`
- **Type**: Read
- **Description**: Returns bot name
- **Parameters**:
  - `bot_id` (int): Bot id
- **Returns**: Bot name string

### 10. cria_get_bot_api_key
- **Class**: `local_cria_external_bot`
- **Method**: `get_api_key`
- **Type**: Read
- **Description**: Returns the bots api key
- **Parameters**:
  - `bot_id` (int): Bot id
- **Returns**: API key string

### 11. cria_bot_exists
- **Class**: `local_cria_external_criabot`
- **Method**: `bot_exists`
- **Type**: Read
- **Description**: Check to see if a bot exists
- **Parameters**:
  - `bot_name` (string): Bot name to check
- **Returns**: Boolean

---

## Bot Type Management APIs

### 12. cria_bot_type_delete
- **Class**: `local_cria_external_bot_type`
- **Method**: `delete`
- **Type**: Write
- **Description**: Delete bot type
- **Parameters**:
  - `id` (int): Bot type id
- **Returns**: Boolean

### 13. cria_get_bot_type_message
- **Class**: `local_cria_external_bot_type`
- **Method**: `get_system_message`
- **Type**: Read
- **Description**: Returns bot type system message
- **Parameters**:
  - `bot_type_id` (int): Bot type id
- **Returns**: System message string

---

## Bot Role Management APIs

### 14. cria_delete_bot_role
- **Class**: `local_cria_external_bot_role`
- **Method**: `delete`
- **Type**: Write
- **Description**: Delete bot role and all permissions associated with it
- **Parameters**:
  - `id` (int): Bot role id
- **Returns**: Boolean

---

## Permission Management APIs

### 15. cria_assign_user_role
- **Class**: `local_cria_external_permission`
- **Method**: `assign_user_role`
- **Type**: Write
- **Description**: Adds a user to a role
- **Parameters**:
  - `user_id` (int): User id
  - `role_id` (int): Role id
- **Returns**: Assignment status

### 16. cria_unassign_user_role
- **Class**: `local_cria_external_permission`
- **Method**: `unassign_user_role`
- **Type**: Write
- **Description**: Removes a user from a role
- **Parameters**:
  - `user_id` (int): User id
  - `role_id` (int): Role id
- **Returns**: Unassignment status

### 17. cria_get_assigned_users
- **Class**: `local_cria_external_permission`
- **Method**: `get_assigned_users`
- **Type**: Read
- **Description**: Get all users assigned to a role
- **Parameters**:
  - `role_id` (int): Role id
- **Returns**: Array of assigned users

### 18. cria_get_users
- **Class**: `local_cria_external_permission`
- **Method**: `get_users`
- **Type**: Read
- **Description**: Get all system users
- **Parameters**: None
- **Returns**: Array of system users

---

## Chat/Conversation APIs

### 19. cria_get_chat_id (DEPRECATED)
- **Class**: `local_cria_external_criabot`
- **Method**: `chat_start`
- **Type**: Read
- **Description**: Now deprecated. Use cria_chat_start instead. Starts a chat session and returns the chat id
- **Parameters**: None
- **Returns**: Chat ID

### 20. cria_chat_start
- **Class**: `local_cria_external_criabot`
- **Method**: `chat_start`
- **Type**: Read
- **Description**: Starts a chat session and returns the chat id
- **Parameters**: None
- **Returns**: Chat ID string

### 21. cria_chat_end
- **Class**: `local_cria_external_criabot`
- **Method**: `chat_end`
- **Type**: Write
- **Description**: Ends a chat session
- **Parameters**:
  - `chat_id` (string): ID of the chat session
- **Returns**: Boolean (true on success)

### 22. cria_chat_history
- **Class**: `local_cria_external_criabot`
- **Method**: `chat_history`
- **Type**: Write
- **Description**: Get chat history for a specific chat id
- **Parameters**:
  - `chat_id` (string): ID of the chat session
- **Returns**: Array of chat messages

### 23. cria_chat_send
- **Class**: `local_cria_external_criabot`
- **Method**: `chat_send`
- **Type**: Write
- **Description**: Send a message to a chat session
- **Parameters**:
  - `chat_id` (string): ID of the chat session
  - `prompt` (string): Message to send
  - `bot_name` (string): Name of the bot
- **Returns**: Response message object

---

## Question Management APIs

### 24. cria_get_answer
- **Class**: `local_cria_external_question`
- **Method**: `get_answer`
- **Type**: Read
- **Description**: Returns answer based on question id
- **Parameters**:
  - `id` (int): Question id
- **Returns**: Answer string

### 25. cria_question_delete
- **Class**: `local_cria_external_question`
- **Method**: `delete`
- **Type**: Write
- **Description**: Deletes question and all examples
- **Parameters**:
  - `id` (int): Question id
- **Returns**: Status code (200 = success, 404 = error)

### 26. cria_question_create
- **Class**: `local_cria_external_question`
- **Method**: `create`
- **Type**: Write
- **Description**: Create a new question and return the question id
- **Parameters**:
  - `intentid` (int): The intent id
  - `name` (string): Name for the question
  - `value` (string): The question being asked
  - `answer` (string): The answer for the question
  - `relatedquestions` (string, optional): A JSON array: [{"label":"Label name", "prompt":"The prompt"}]
  - `lang` (string, optional): Language code (default: 'en')
  - `generateanswer` (int, optional): Whether the answer should be returned as is or paraphrased by LLM (default: 0)
  - `examplequestions` (string, optional): JSON of examples [{"value":"An example question"}]
- **Returns**: New question ID

### 27. cria_question_delete_all
- **Class**: `local_cria_external_question`
- **Method**: `delete_all`
- **Type**: Write
- **Description**: Deletes all questions for an intent
- **Parameters**:
  - `intent_id` (int): Intent id
- **Returns**: Deletion status

### 28. cria_question_publish
- **Class**: `local_cria_external_question`
- **Method**: `publish`
- **Type**: Write
- **Description**: Publish a question to criabot
- **Parameters**:
  - `question_id` (int): Question id
- **Returns**: Publication status

### 29. cria_question_example_update
- **Class**: `local_cria_external_question`
- **Method**: `update_example`
- **Type**: Write
- **Description**: Update example question
- **Parameters**:
  - `id` (int): Example id
  - `value` (string): Updated example text
- **Returns**: Update status

### 30. cria_question_example_delete
- **Class**: `local_cria_external_question`
- **Method**: `delete_example`
- **Type**: Write
- **Description**: Delete example question
- **Parameters**:
  - `id` (int): Example id
- **Returns**: Deletion status

### 31. cria_question_example_insert
- **Class**: `local_cria_external_question`
- **Method**: `insert_example`
- **Type**: Write
- **Description**: Add example question
- **Parameters**:
  - `question_id` (int): Question id
  - `value` (string): Example question text
- **Returns**: New example ID

---

## Synonym Management APIs

### 32. cria_synonym_update
- **Class**: `local_cria_external_synonym`
- **Method**: `update`
- **Type**: Write
- **Description**: Update synonym
- **Parameters**:
  - `id` (int): Synonym id
  - `value` (string): Updated synonym value
- **Returns**: Update status

### 33. cria_synonym_delete
- **Class**: `local_cria_external_synonym`
- **Method**: `delete`
- **Type**: Write
- **Description**: Delete synonym
- **Parameters**:
  - `id` (int): Synonym id
- **Returns**: Boolean

### 34. cria_synonym_insert
- **Class**: `local_cria_external_synonym`
- **Method**: `insert`
- **Type**: Write
- **Description**: Add synonym
- **Parameters**:
  - `keyword_id` (int): Keyword id
  - `value` (string): Synonym value
- **Returns**: New synonym ID

---

## Model Management APIs

### 35. cria_get_model_max_tokens
- **Class**: `local_cria_external_models`
- **Method**: `get_max_tokens`
- **Type**: Read
- **Description**: Get max tokens
- **Parameters**:
  - `model_id` (int): Model id
- **Returns**: Maximum token count

### 36. cria_model_delete
- **Class**: `local_cria_external_models`
- **Method**: `delete`
- **Type**: Read
- **Description**: Delete Model
- **Parameters**:
  - `id` (int): Model id
- **Returns**: Deletion status

---

## GPT/AI Response APIs

### 37. cria_get_gpt_response
- **Class**: `local_cria_external_gpt`
- **Method**: `response`
- **Type**: Read
- **Description**: Returns response from OpenAI
- **Parameters**:
  - `prompt` (string): The prompt to send to GPT
  - `model` (string, optional): Model to use
  - `max_tokens` (int, optional): Maximum tokens
  - `temperature` (float, optional): Temperature setting
- **Returns**: GPT response string

---

## Logging APIs

### 38. cria_insert_log
- **Class**: `local_cria_external_logs`
- **Method**: `insert_log`
- **Type**: Write
- **Description**: Insert log record
- **Parameters**:
  - `level` (string): Log level (info, warning, error, etc.)
  - `message` (string): Log message
  - `context` (string, optional): Additional context data
- **Returns**: New log ID

---

## System Configuration APIs

### 39. cria_get_config
- **Class**: `local_cria_external_cria`
- **Method**: `get_config`
- **Type**: Read
- **Description**: Get cria config
- **Parameters**: None
- **Returns**: Configuration object

### 40. cria_get_availability
- **Class**: `local_cria_external_cria`
- **Method**: `get_cria_availability`
- **Type**: Write
- **Description**: Check to see if Cria is available based on maintenance mode
- **Parameters**: None
- **Returns**: Availability status

---

## API Usage Notes

### General Structure
All external APIs follow the Moodle external API pattern:
- Parameters are defined in `{method_name}_parameters()` methods
- Main logic is implemented in the method itself
- Return types are defined in `{method_name}_returns()` methods
- All APIs include parameter validation and context validation
- Most APIs require system context validation

### Parameter Types
- `PARAM_INT`: Integer values
- `PARAM_TEXT`: Plain text strings
- `PARAM_RAW`: Raw text (can include HTML/special characters)
- `PARAM_BOOL`: Boolean values (true/false)

### Authentication
- All APIs are AJAX-enabled
- Context validation is performed using `\context_system::instance()`
- Some APIs have specific capability requirements (e.g., `local/cria:edit_bot_content`)

### Error Handling
- APIs return appropriate status codes (200 for success, 404 for errors)
- Parameter validation is performed using `self::validate_parameters()`
- Context validation is performed using `self::validate_context()`

### File Locations
- Service definitions: `db/services.php`
- Implementation classes: `classes/external/*.php`
- Each external class extends `external_api`

This documentation covers all 40 external API calls available in the Cria system as of the current codebase.
