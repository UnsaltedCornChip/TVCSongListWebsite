# TVC Twitch Song List

**TVC Twitch Song List** is a PHP-based web application designed for managing and displaying a collection of YouTube music videos for a Twitch community. Users can browse songs by category (e.g., Originals, Covers), artist, or recency, and authenticated users (streamers/moderators) can manage the video database via an admin interface. The app integrates with Twitch for authentication, YouTube Data API for video/playlist imports, and PostgreSQL for data storage.

## Features

* Public Access:
  * Browse all songs, newest additions, or by category (e.g., Originals, Covers, Loops, Uke/Vocals).
  * View songs by artist or select a random song.
  * Responsive design with light/dark mode toggle.
* Authenticated User Access:
  * Login via Twitch OAuth for streamers, moderators, or viewers.
  * Admin panel (streamers/moderators only) for:
    * Adding individual YouTube videos.
    * Bulk uploading videos.
    * Importing YouTube playlists (automatically categorizes videos).
    * Managing categories and inactive videos.
* Integrations:
  * Twitch API: OAuth-based authentication and role-based access (streamer, moderator, viewer).
  * YouTube Data API v3: Fetch video metadata, thumbnails, durations, and playlist contents.
  * PostgreSQL: Stores videos, categories, and their relationships.

## Project Structure

The repository is organized as follows:

```
├── images/
│   ├── favicon.png            # Site favicon
│   ├── tvc.png                # Main logo
├── includes/
│   ├── footer.php             # Footer HTML
│   ├── header.php             # Navigation bar with dynamic admin links
├── add_video_to_db.php        # Admin: Add single YouTube video
├── add_video.php              # Admin: Add single YouTube video
├── all-songs.php              # Display all active songs
├── artists.php                # List all artists
├── bulk_upload.php            # Admin: Load data from a previous database via CSV
├── cacert.pem                 # all public certs for making curl requests
├── category.php               # Retrieve and list videos in specific categories
├── covers.php                 # Display songs in "Covers" category
├── edit_video.php             # Admin: Edit video metadata
├── import_playlist.php        # Admin: Import YouTube playlist
├── index.php                  # Homepage
├── logout.php                 # Logout of the website/destroy session
├── loops.php                  # Display songs in "Loops" category
├── manage_categories.php      # Admin: Manage categories
├── manage_inactive_videos.php # Admin: Manage inactive videos
├── newest-additions.php       # Display recently added songs
├── originals.php              # Display songs in "Originals" category
├── privacy-policy.php         # The privacy policy for this website
├── random.php                 # Display 20 random songs
├── refresh_video.php          # Refresh video metadata from YouTube
├── script.js                  # Client-side JavaScript (theme toggle, copy commands)
├── styles.css                 # CSS styles (responsive, light/dark modes)
├── terms-of-service.php       # The terms of service for this website
├── twitch_callback.php        # Process Twitch login information and store in session
├── twitch_login.php           # Twitch OAuth login
├── uke-vocals.php             # Display songs in "Uke/Vocals" category
├── .env.example               # Example environment variables
├── .gitignore                 # Git ignore rules
└── README.md                  # This file
```

### Key Files

* index.php: Homepage with welcome message and navigation.
* header.php: Dynamic navigation bar; shows admin options for streamers/moderators.
* add_video.php: Form to add a single YouTube video (title, artist, thumbnail, duration, categories).
* import_playlist.php: Import a YouTube playlist, creating a category from the playlist name and linking videos.
* bulk_upload.php: Upload multiple videos at once.
* all-songs.php: Paginated list of all active videos.
* styles.css: Defines responsive styles with CSS variables for theming.
* script.js: Handles theme toggling and copy-to-clipboard for song commands.
* twitch_login.php: Manages Twitch OAuth login flow.

## Integrations

* Twitch API:
  * OAuth 2.0 authentication via `twitch_login.php`.
  * Stores user data in `$_SESSION['twitch_user']` (e.g., `login`, `is_streamer`, `is_moderator`, `is_viewer`).
  * Role-based access control for admin features.
* YouTube Data API v3:
  * Used in `add_video.php`, `bulk_upload.php`, `import_playlist.php`.
  * Endpoints:
    * `videos?part=snippet,contentDetails`: Fetch video metadata (title, channelTitle as artist, thumbnail, duration).
    * `playlists?part=snippet`: Get playlist name.
    * `playlistItems?part=contentDetails`: List videos in a playlist.
  * Requires `YOUTUBE_API_KEY` (set in `.env` or Azure).
* PostgreSQL:
  * Hosted database (e.g., Azure PostgreSQL).
  * Stores videos, categories, and relationships.
  * Connection variables are on each page connecting to the database.
  * Environment variables hold the `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS` values.

## Database Schema

The application uses three PostgreSQL tables:

### youtube_videos

* Stores YouTube video metadata.

```sql
CREATE TABLE public.youtube_videos (
    id serial4 NOT NULL,
    video_id text NOT NULL,
    title text NOT NULL,
    artist text NOT NULL,
    thumbnail_link text NOT NULL,
    length_seconds int4 NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    status varchar(20) DEFAULT 'active',
    CONSTRAINT youtube_videos_pkey PRIMARY KEY (id),
    CONSTRAINT youtube_videos_status_check CHECK (status IN ('active', 'inactive')),
    CONSTRAINT youtube_videos_video_id_key UNIQUE (video_id)
);
```

* video_id: YouTube video ID (e.g., `dQw4w9WgXcQ`).
* length_seconds: Duration in seconds (converted from ISO 8601).
* status: `'active'` or `'inactive'` (only active videos shown publicly).

### categories

* Stores video categories (e.g., Metal, Pop, Chill, etc).

```sql
CREATE TABLE public.categories (
    id serial4 NOT NULL,
    name text NOT NULL,
    CONSTRAINT categories_name_key UNIQUE (name),
    CONSTRAINT categories_pkey PRIMARY KEY (id)
);
```

* name: Category name (e.g., Metal, Pop, Chill, etc).

### video_categories

* Links videos to categories (many-to-many).

```sql
CREATE TABLE public.video_categories (
    id serial4 NOT NULL,
    video_id text NOT NULL,
    category_id int4 NOT NULL,
    CONSTRAINT video_categories_pkey PRIMARY KEY (id),
    CONSTRAINT video_categories_video_id_category_id_key UNIQUE (video_id, category_id),
    CONSTRAINT video_categories_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE,
    CONSTRAINT video_categories_video_id_fkey FOREIGN KEY (video_id) REFERENCES public.youtube_videos(video_id) ON DELETE CASCADE
);
CREATE INDEX idx_video_categories_category_id ON public.video_categories USING btree (category_id);
CREATE INDEX idx_video_categories_video_id ON public.video_categories USING btree (video_id);
```

* Ensures each video-category pair is unique.
* Cascading deletes maintain referential integrity.

## Environment Variables

The app uses environment variables for sensitive configuration, stored in a `.env` file during development (see `.env.example`). In production, these are set in Azure App Service under **Settings > Environment variables**.

`.env.example`

```
DB_HOST=your-postgres-host
DB_NAME=your-database-name
DB_USER=your-database-user
DB_PASS=your-database-password
YOUTUBE_API_KEY=your-youtube-api-key
TWITCH_CLIENT_ID=your-twitch-client-id
TWITCH_CLIENT_SECRET=your-twitch-client-secret
TWITCH_REDIRECT_URI=https://your-domain.com/twitch_callback.php
```

* **DB_HOST**: PostgreSQL server hostname (e.g., `your-postgres.postgres.database.azure.com`).
* **DB_NAME**: Database name.
* **DB_USER**: Database username.
* **DB_PASS**: Database password.
* **YOUTUBE_API_KEY**: Google API key with YouTube Data API v3 enabled from https://console.cloud.google.com.
* **TWITCH_CLIENT_ID**: Twitch app client ID from https://dev.twitch.tv.
* **TWITCH_CLIENT_SECRET**: Twitch app client secret from https://dev.twitch.tv.
* **TWITCH_REDIRECT_URI**: Callback URL for Twitch OAuth (must match Azure domain).

### Azure Setup

In Azure App Service:

1. Navigate to **Settings > Environment variables**.
1. Add each variable as a **Name-Value pair** under **Application settings** or **Connection strings** (for database).
   * Example: `DB_HOST` = `your-postgres.postgres.database.azure.com`.
   * Use **Connection strings** for database credentials if preferred (e.g., pgsql:host=...).
1. Save and restart the app.

## Prerequisites

* **PHP**: 7.4 or higher (with `pdo_pgsql`, `curl` extensions).
* **Composer**: For dependency management (optional, none currently).
* **PostgreSQL**: 11 or higher.
* **Azure Account**: For deployment.
* **Git**: For cloning the repository.
* **Twitch Developer Account**: For OAuth credentials.
* **Google Cloud Account**: For YouTube API key.

## Setup Instructions (Local Development)

1. Clone the Repository:
   ```bash
   git clone https://github.com/UnsaltedCornChip/TVCSongListWebsite.git
   cd TVCSongListWebsite
   ```
1. Install PHP and Extensions:
   * Ensure PHP is installed with pdo_pgsql and curl.
   * Example (Ubuntu):
   ```bash
   sudo apt-get install php php-pgsql php-curl
   ```
1. Set Up PostgreSQL:
   * Create a database and user.
   * Apply the schema (from above):
   ```bash
   psql -h localhost -U your_user -d your_db -f schema.sql
   ```
   * Save the schema as `schema.sql` and run it.
1. Configure Environment Variables:
   * Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
   * Edit `.env` with your values:
      * Database credentials.
      * YouTube API key (from Google Cloud Console).
      * Twitch credentials (from Twitch Developer Console).
      * Set `TWITCH_REDIRECT_URI` to `http://localhost:8080/twitch_callback.php` for local testing.
1. Run a Local Server:
   * Use PHP’s built-in server:
   ```bash
   php -S localhost:8080
   ```
   * Access at http://localhost:8080.
1. Test:
   * Login via Twitch (`twitch_login.php`).
   * Add a video (`add_video.php`).
   * Import a playlist (`import_playlist.php`).
   * Browse songs (`all-songs.php`, `originals.php`).

## Deploying to Azure
Follow these steps to deploy the app on Azure App Service with PostgreSQL.

1. Set Up Azure Resources
   * Azure App Service:
      * Create a new App Service (Linux, PHP 8.x).
         * Portal: Create a **resource > Web App**.
         * Choose **Subscription, Resource Group, Name, Region**.
         * Select **PHP 8.x** stack, **Linux** OS.
         * Use **Free** or higher pricing tier.
      * Note the app URL (e.g., `https://your-app.azurewebsites.net`).
   * Azure Database for PostgreSQL:
      * Create a PostgreSQL server.
         * Portal: **Create a resource > Azure Database for PostgreSQL > Flexible Server**.
         * Set **Server name, Region, Admin username, Password**.
         * Enable **Public access** and add a firewall rule for Azure services (or your IP for testing).
      * Create a database (e.g., `songlist`).
      * Apply the schema:
      ```bash
      psql -h your-postgres.postgres.database.azure.com -U your_user -d songlist
      ```
      * Paste the schema SQL above.
1. Configure Twitch and YouTube
   * Twitch:
      * In Twitch Developer Console:
         * Create an app.
         * Set **OAuth Redirect URLs** to `https://your-app.azurewebsites.net/twitch_callback.php`.
         * Copy **Client ID** and **Client Secret**.
   * YouTube:
      * In Google Cloud Console:
         * Create a project.
         * Enable **YouTube Data API v3**.
         * Create an API key (under **Credentials**).
         * Ensure quota is sufficient (~10,000 units/day; playlist import uses ~2000 units for 500 videos).
1. Deploy the Code
   * Option 1: GitHub Actions:
      * Fork or use this repository.
      * In Azure Portal:
         * Go to **App Service > Deployment Center**.
         * Select **GitHub** as the source.
         * Authorize Azure to access your repository.
         * Configure the workflow (auto-generated YAML in `.github/workflows/`).
      * Push changes to trigger deployment.
   * Option 2: Local Git:
      * Initialize a Git repo in Azure:
      ```bash
      az webapp deployment source config-local-git --name your-app --resource-group your-group
      ```
      * Copy the Git URL (e.g., `https://your-app.scm.azurewebsites.net/your-app.git`).
      * Add remote and push:
      ```bash
      git remote add azure <git-url>
      git push azure master
      ```
   * Option 3: ZIP Deploy:
      * Zip the repository (excluding `.git`, `.env`).
      * Use Azure CLI:
      ```bash
      az webapp deploy --resource-group your-group --name your-app --src-path app.zip
      ```
1. Configure Environment Variables
   * In Azure Portal:
      * Go to **App Service > Settings > Environment variables**.
      * Add:
         * `DB_HOST`: `your-postgres.postgres.database.azure.com`
         * `DB_NAME`: `songlist`
         * `DB_USER`: `your_user`
         * `DB_PASS`: `your_password`
         * `YOUTUBE_API_KEY`: Your API key
        * `TWITCH_CLIENT_ID`: Your client ID
         * `TWITCH_CLIENT_SECRET`: Your client secret
         * `TWITCH_REDIRECT_URI`: `https://your-app.azurewebsites.net/twitch_login.php`
      * Save and restart the app.
1. Test the Deployment
   * Access `https://your-app.azurewebsites.net`.
   * Login via Twitch.
   * Test admin features (add a video, import a YouTube playlist).
   * Monitor logs:
   ```bash
   az webapp log tail --name your-app --resource-group your-group
   ```

## Troubleshooting

* Database Connection:
   * Verify `DB_*` variables.
   * Check PostgreSQL firewall rules.
   * Ensure SSL (`sslmode=require`) is supported.
* Twitch Login:
   * Confirm `TWITCH_REDIRECT_URI` matches Azure URL.
   * Check client ID/secret.
* YouTube API:
   * Verify API key and quota.
   * Test with a public playlist (e.g., `https://www.youtube.com/playlist?list=PLlUZ3i-FUgHqk9-C-Fw_C6YsvTyx2c8nc`).
* Deployment:
   * Check Azure logs for PHP errors.
   * Ensure PHP extensions (`pdo_pgsql`, `curl`) are enabled.

## Contributing

1. Fork the repository.
1. Create a feature branch (`git checkout -b feature/your-feature`).
1. Commit changes (`git commit -m 'Add feature'`).
1. Push to the branch (`git push origin feature/your-feature`).
1. Open a pull request.

## License

This project is licensed under the MIT License. See LICENSE for details.

## Contact

For issues or questions, open an issue on GitHub or contact the repository owner.
