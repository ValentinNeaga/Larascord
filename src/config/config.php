<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application ID
    |--------------------------------------------------------------------------
    |
    | This is the ID of your Discord application.
    |
    */

    'client_id' => env('LARASCORD_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Application Secret
    |--------------------------------------------------------------------------
    |
    | This is the secret of your Discord application.
    |
    */

    'client_secret' => env('LARASCORD_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Application Access Token
    |--------------------------------------------------------------------------
    |
    | This is the bot token of your Discord application. It is only required if
    | you want to add users to a guild through the "guilds.join" scope.
    |
    */

    'access_token' => env('LARASCORD_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Grant Type
    |--------------------------------------------------------------------------
    |
    | This is the grant type of your Discord application. It must be set to
    | "authorization_code".
    |
    */

    'grant_type' => env('LARASCORD_GRANT_TYPE', 'authorization_code'),

    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    |
    | This is the URI that Discord will redirect to after the user authorizes
    | your application. It has to match one of the redirect URLs listed in the
    | OAuth2 tab of your Discord application.
    |
    */

    'redirect_uri' => env('LARASCORD_REDIRECT_URI', env('APP_URL', 'http://localhost:8000') . '/' . env('LARASCORD_PREFIX', 'larascord') . '/callback'),

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | These are the OAuth2 scopes Larascord will ask Discord for.
    |
    */

    'scopes' => array_values(array_filter(array_map('trim', explode(',', env('LARASCORD_SCOPES', 'identify,email'))))),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Prompt - "none" or "consent"
    |--------------------------------------------------------------------------
    |
    | The prompt controls how the authorization flow handles existing authorizations.
    | If a user has previously authorized your application with the requested scopes
    | and prompt is set to consent,it will request them to re-approve their
    | authorization. If set to none, it will skip the authorization screen
    | and redirect them back to your redirect URI without requesting
    | their authorization.
    |
    */

    'prompt' => env('LARASCORD_PROMPT', 'none'),

    /*
    |--------------------------------------------------------------------------
    | State Verification
    |--------------------------------------------------------------------------
    |
    | Larascord sends a random "state" parameter to Discord and verifies it when
    | the user comes back. This protects the callback route against cross-site
    | request forgery. Only turn this off if you build the authorization URL
    | yourself and cannot keep the state in the session.
    |
    */

    'verify_state' => true,

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Larascord registers its own routes under the prefix below. Every route is
    | named with a "larascord." prefix so it can never collide with the routes
    | of your application. Set "enabled" to false if you would rather point
    | your own routes to Jakyeru\Larascord\Http\Controllers\DiscordController.
    |
    | The "login_alias" option registers a "/login" route named "login" that
    | sends the visitor straight to Discord. Leave it disabled if your
    | application already has a route with that name.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => env('LARASCORD_PREFIX', 'larascord'),
        'middleware' => ['web'],
        'authenticated_middleware' => ['web', 'auth'],
        'login_alias' => env('LARASCORD_LOGIN_ALIAS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | This is the guard Larascord uses to log the user in and out.
    |
    */

    'guard' => env('LARASCORD_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | Larascord never touches your users table. It keeps the Discord profile in
    | its own table and links it to one of your users through a foreign key.
    |
    | The "attributes" array maps the columns of your users table to the
    | attributes of the Discord profile. Available attributes are: id,
    | username, global_name, display_name, discriminator, avatar,
    | email, verified, banner, banner_color, accent_color,
    | locale, mfa_enabled, premium_type, public_flags.
    |
    | "link_by_email" links a Discord account to an existing user when their
    | e-mail addresses match, which is what you want when you are adding
    | Discord login to an application that already has registered users.
    |
    | "create_missing" decides whether Larascord may create a user when there
    | is nothing to link the Discord account to. Turn it off to keep your
    | application invite-only.
    |
    | "fill_random_password" gives the created user a random hashed password so
    | that a non-nullable password column does not reject the insert. The
    | password is never shown to anyone and cannot be guessed.
    |
    */

    'users' => [
        'model' => env('LARASCORD_USER_MODEL', 'App\Models\User'),
        'email_column' => 'email',
        'attributes' => [
            'name' => 'display_name',
            'email' => 'email',
        ],
        'link_by_email' => true,
        'create_missing' => true,
        'fill_random_password' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | These are the tables Larascord owns. Dropping them is all it takes to
    | remove every trace of Larascord from your database.
    |
    | Set "foreign_keys" to false if your users table lives on another
    | connection or does not support foreign key constraints.
    |
    */

    'database' => [
        'connection' => env('LARASCORD_DB_CONNECTION'),
        'accounts_table' => 'larascord_accounts',
        'access_tokens_table' => 'larascord_access_tokens',
        'foreign_keys' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Restrict Access to Specific Guilds
    |--------------------------------------------------------------------------
    |
    | This option restricts access to the application to users who are members
    | of specific Discord guilds. Users who are not members of the specified
    | guilds will not be able to use the application.
    |
    */

    'guilds' => [],

    /*
    |--------------------------------------------------------------------------
    | Restrict Access to Specific Guilds - Strict Mode
    |--------------------------------------------------------------------------
    |
    | Enabling this option will require the user to be a member of ALL the
    | aforementioned guilds. If this option is disabled, the user will
    | only need to be a member of at least ONE of the guilds.
    |
    */

    'guilds_strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Restrict Access to Specific Roles
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, the user will only be able to use the
    | application if they have at least one of the specified roles.
    |
    */

    // WARNING: This feature makes one request to the Discord API for each guild you specify. (Because you need to fetch the roles for each guild)

    'guild_roles' => [
        // 'guild_id' => [
        //     'role_id',
        //     'role_id',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remember Me
    |--------------------------------------------------------------------------
    |
    | Whether or not to remember the user after they log in.
    |
    */

    'remember_me' => false,

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Where to send the user after they log in, after they log out and after
    | they unlink their Discord account.
    |
    */

    'redirect_login' => '/',

    'redirect_logout' => '/',

    'redirect_unlink' => '/',

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    |
    | These are the error messages that will be displayed to the user if there
    | is an error.
    |
    */

    'error_messages' => [
        'missing_code' => [
            'message' => 'The authorization code is missing.',
            'redirect' => '/'
        ],
        'invalid_code' => [
            'message' => 'The authorization code is invalid.',
            'redirect' => '/'
        ],
        'invalid_state' => [
            'message' => 'The login attempt has expired. Please try again.',
            'redirect' => '/'
        ],
        'authorization_failed' => [
            'message' => 'The authorization failed.',
            'redirect' => '/'
        ],
        'missing_email' => [
            'message' => 'Couldn\'t get your e-mail address.',
            'redirect' => '/'
        ],
        'account_already_linked' => [
            'message' => 'This Discord account is already linked to another user.',
            'redirect' => '/'
        ],
        'registration_disabled' => [
            'message' => 'There is no account matching your Discord account.',
            'redirect' => '/'
        ],
        'database_error' => [
            'message' => 'There was an error with the database. Please try again later.',
            'redirect' => '/'
        ],
        'missing_guilds_scope' => [
            'message' => 'The "guilds" scope is required.',
            'redirect' => '/'
        ],
        'missing_guilds_members_read_scope' => [
            'message' => 'The "guilds" and "guilds.members.read" scopes are required.',
            'redirect' => '/'
        ],
        'authorization_failed_guilds' => [
            'message' => 'Couldn\'t get the servers you\'re in.',
            'redirect' => '/'
        ],
        'not_member_guild_only' => [
            'message' => 'You are not a member of the required guilds.',
            'redirect' => '/'
        ],
        'missing_access_token' => [
            'message' => 'The access token is missing.',
            'redirect' => '/'
        ],
        'authorization_failed_roles' => [
            'message' => 'Couldn\'t get the roles you have.',
            'redirect' => '/'
        ],
        'missing_role' => [
            'message' => 'You don\'t have the required roles.',
            'redirect' => '/'
        ],
        'revoke_token_failed' => [
            'message' => 'An error occurred while trying to revoke your access token.',
            'redirect' => '/'
        ],
        'missing_discord_account' => [
            'message' => 'You don\'t have a Discord account linked.',
            'redirect' => '/'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Success Messages
    |--------------------------------------------------------------------------
    |
    | These are the success messages that will be displayed to the user if there
    | is no error.
    |
    */

    'success_messages' => [
        'account_linked' => [
            'message' => 'Your Discord account has been linked.',
            'redirect' => null
        ],
        'account_unlinked' => [
            'message' => 'Your Discord account has been unlinked.',
            'redirect' => null
        ],
    ],

];
