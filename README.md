# FurEver Memories

FurEver Memories is a warm, modern memorial platform for beloved pets. It combines digital storytelling with QR sharing, guest tributes, memorial galleries, music, and printed-keepsake friendly experiences.

The product is designed to feel loving, peaceful, and celebratory rather than sad or funeral-like.

## Highlights

- Public pet memorial pages with custom branding and storytelling
- Admin and client dashboards for managing memorial content
- Memorial builder for pet details, cover images, gallery, timelines, music, and tribute content
- Message wall with moderation workflow
- Candle and heart reactions with named tribute badges
- Share and invite flows with QR support, Facebook sharing, and copy-link tools
- Background music playlist with consent prompt and compact floating player
- Branded marketing homepage for FurEver Memories
- Role-based authentication for administrators and clients
- Email verification and audit logging

## Core Experience

### Public Memorial Page

Each memorial page can include:

- Hero section with branded logo and pet cover image
- Short tribute
- Story / Timeline of Memories
- Photo gallery
- Video tribute
- Message wall
- Reactions section for candles and hearts
- Final letter
- Invite and sharing section
- View count and access information
- Suggestion / support contact modal

### Admin / Client Experience

The admin side allows:

- Managing client accounts
- Configuring memorial pages
- Moderating visitor messages
- Viewing audit logs
- Managing profile and password settings
- Downloading QR codes for memorial pages

## Tech Stack

- PHP
- MySQL / MariaDB
- Bootstrap 5
- Vanilla JavaScript
- PDO for database access
- `gumlet/php-image-resize` for image optimization
- CKEditor 5 for rich-text content editing

## Project Structure

```text
furever_memories/
├── assets/
│   ├── css/
│   ├── images/
│   └── js/
├── config/
│   ├── config.php
│   └── db.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── mailer.php
│   ├── topbar.php
│   └── upload_helpers.php
├── modules/
│   ├── module_marketing_home.php
│   ├── module_petcoverpage.php
│   ├── module_storytimeline.php
│   ├── module_petimagecarousell.php
│   ├── module_video_tribute.php
│   ├── module_messages.php
│   ├── module_reactions.php
│   ├── module_final_letter.php
│   ├── module_footer.php
│   └── module_music_player.php
├── uploads/
├── vendor/
├── index.php
├── login.php
├── dashboard.php
├── memorial_edit.php
├── moderation.php
├── admin_clients.php
├── client_profile.php
├── change_password.php
├── audit_logs.php
├── verify_email.php
└── prod_furever_memories_db.sql
```

## User Roles

### Administrator

- Create and manage client accounts
- Open and configure any client memorial page
- Review audit logs
- Resend verification links

### Client

- Manage their own memorial page
- Update profile details
- Download public QR code
- Moderate submitted messages

## Routes and Access Pattern

### Marketing Homepage

Default homepage:

```text
/index.php
```

### Public Memorial Page

Local development:

```text
/index.php?c={client_guid}
```

Production-style route support:

```text
/c/{client_guid}
```

### Admin / Builder Pages

- `/dashboard.php`
- `/memorial_edit.php`
- `/moderation.php`
- `/admin_clients.php`
- `/client_profile.php`
- `/change_password.php`
- `/audit_logs.php`

## Local Development Setup

### 1. Requirements

- XAMPP, WAMP, or equivalent local PHP + MySQL stack
- PHP 8.x recommended
- MySQL or MariaDB
- Composer

### 2. Required PHP Extensions

Make sure these are available in your PHP environment:

- `pdo_mysql`
- `gd`
- `curl`
- `mbstring`
- `fileinfo`

### 3. Clone or Copy the Project

Place the project inside your web root. Example:

```text
C:\xampp\htdocs\furever_memories
```

### 4. Install Dependencies

From the project root:

```bash
composer install
```

### 5. Create the Database

Import the provided SQL file into MySQL / MariaDB:

```text
prod_furever_memories_db.sql
```

### 6. Configure Local Settings

Create or update:

```text
config/config.php
```

Important:

- Keep local credentials and API keys out of version control
- Do not commit production secrets
- Use environment-specific values for database and mail configuration

At minimum, configure these values locally:

- database host
- database name
- database user
- database password
- app base URL
- upload directory and upload URL
- mail sender settings
- mail API settings if used

### 7. Make Upload Directories Writable

The application writes files to:

```text
uploads/
uploads/music/
```

Make sure PHP can create folders and write files there.

### 8. Open the App

Typical local URLs:

```text
http://127.0.0.1/furever_memories/
http://localhost/furever_memories/
```

## Configuration Notes

The app uses a local config file for environment-specific settings such as:

- database connection
- app URL
- upload path
- mail sender identity
- optional mail API integration

This file should stay local to the environment and must not expose:

- API keys
- SMTP credentials
- passwords
- production-only host information not meant for the repository

## Database Notes

The application uses a memorial-centered structure for:

- users
- memorial pages
- memorial timelines
- memorial gallery
- memorial playlist
- memorial messages
- memorial reactions
- email verification tokens
- audit logs
- memorial page views

Recent updates standardize playlist entries around `memorial_page_id` and include page view tracking support.

## Media Handling

### Images

- Uploaded images are resized and optimized on save
- Supported formats: JPG, PNG, WEBP

### Video

- Local upload support for memorial video files
- Embedded YouTube support

### Music

- Background music playlist supports multiple tracks
- MP3 uploads are retained as playlist items
- Player includes consent prompt, compact floating mode, and minimize / show behavior

## Content Features

### Rich Text

The builder supports rich text for:

- short tribute
- timeline descriptions
- final letter
- footer tribute text

The public page renders sanitized rich text output instead of showing raw HTML tags.

### Timeline and Gallery

- Timeline supports dates, titles, descriptions, and photos
- Gallery supports multiple uploaded photos
- Builder provides image previews for selected uploads

### Visitor Participation

Visitors can:

- leave messages
- upload a photo with a message
- light a candle
- send a heart
- share the memorial page

Message submissions can be moderated before appearing publicly.

## Branding Direction

The current UI is aligned to the FurEver Memories brand:

- warm neutrals
- gold / tan accents
- soft premium memorial tone
- modern, family-friendly presentation

The experience is intentionally designed to feel:

- warm
- peaceful
- loving
- celebratory

## Security Notes

The application includes:

- CSRF protection
- password hashing
- brute-force login attempt limiting
- role-based access checks
- audit logging
- email verification support
- HTML sanitization for rich-text output

## Recommended Git Practices

- Keep `config/config.php` local-only when possible
- Do not commit uploaded memorial assets unless intentionally versioning demo content
- Avoid committing API keys, secrets, and personal credentials
- Test locally before any live deployment

## Deployment Notes

Recommended workflow:

1. Build and test changes locally
2. Commit and push to GitHub
3. Back up live files and database
4. Deploy only approved changes
5. Smoke test the live memorial page, admin pages, uploads, and sharing flows

## Troubleshooting

### Images fail to process

Check that:

- `gd` is enabled
- upload folders are writable
- the file type is JPG, PNG, or WEBP

### Music does not play

Check that:

- uploaded files are valid MP3 files
- the playlist rows are saved in the database
- the browser has not muted the player for the current session

### Public page links look wrong locally

Check that your local `BASE_URL` matches the local folder name and host:

```text
http://127.0.0.1/furever_memories
```

### Login or session issues

Check:

- PHP sessions are enabled
- cookies are allowed in the browser
- local host and base URL are correct

## Future Improvement Ideas

- environment-based config loading
- stronger admin dashboard analytics
- package / pricing management
- improved deployment automation
- richer QR and print fulfillment workflows
- optional testimonial and feedback storage in the database

## License

This repository currently does not declare a formal license. Add one if you plan to distribute or open-source the project.
