# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based web application for creating and managing journal entries. However, the most distinctive and important feature is its **reverse storytelling art project through git commit messages**. The application provides a web interface for posting content, managing git operations, and viewing posts, but its primary artistic purpose is using git commits to tell a continuous story in reverse chronological order.

## The Reverse Storytelling Art Project

This is the core conceptual art piece of quick.robnugen.com. The system implements sequential storytelling through git commit messages where the entire git history, when read chronologically, tells a coherent narrative.

### How It Works
1. **Master Story File**: A complete story is stored in a text file (`$config->storyFile`, typically `/home/barefoot_rob/x0x0x0/x0x0x0.txt`)
2. **Sequential Commit Messages**: Each git commit message represents the next word in the story sequence
3. **Reverse Pattern Matching**: The `NextStoryWord` class reads recent git commits and finds where that sequence appears in the master story
4. **Next Word Generation**: Returns the word that comes immediately **before** the current commit sequence in the story file as the next commit message
5. **Continuous Narrative**: Over time, the git log becomes a readable story when viewed chronologically (oldest to newest)

### Technical Implementation
- **`NextStoryWord` Class**: Core algorithm that matches current git log against story file
- **Pattern Recognition**: Finds the current sequence of 5-15 recent commits within the larger story text
- **Word Extraction**: Handles empty lines using full-width space character `　` (git-compatible)
- **Error Recovery**: Detailed debugging system for when story sequence breaks
- **Automated Integration**: Every commit automatically gets the next story word as its message

### The Artistic Concept
This creates a unique intersection of software development and narrative art where:
- Git's distributed version control becomes a medium for sequential literature
- The mundane act of committing code changes becomes part of a continuous story
- The repository's technical history doubles as a literary work
- Each commit contributes to a larger narrative arc that unfolds over time
- The story progresses backwards through the commit history, creating a reverse-chronological reading experience

### Critical Implementation Details
- **Story Synchronization**: If commits get out of sync with the story, detailed recovery instructions are provided
- **Debug Levels**: Use `?debug=1-6` to troubleshoot story matching issues
- **Directory Dependencies**: Story file path and git repository must be properly configured
- **Word Boundary Handling**: Properly splits multi-word lines and handles punctuation
- **Commit Message Constraints**: Uses git-compatible characters including full-width space for empty lines

## Architecture

### Core Structure
- `prepend.php` - Main bootstrap file that initializes autoloader, database, authentication, and session management
- `classes/` - Contains all PHP classes following a simple autoloader pattern
- `public/` - Web-accessible directory with page controllers
- `templates/` - PHP template files for rendering HTML

### Key Classes
- `Base` - Singleton database connection manager
- `Template` - Simple template engine for rendering views
- `QuickPoster` - Handles creating blog posts with frontmatter and file operations
- `TempOSpooner` - Git operations wrapper (add, commit, push)
- `NextStoryWord` - **CRITICAL**: Implements the reverse storytelling art project by generating sequential story words as commit messages
- `Auth\IsLoggedIn` - Authentication system
- `Database\Database` - Database abstraction layer

### Application Flow
1. All requests go through `prepend.php` for initialization
2. Authentication check happens globally - unauthenticated users see login form
3. Main pages: poster (create), list (view), commit (git operations)
4. **STORY INTEGRATION**: Every git commit automatically uses `NextStoryWord` to generate the next sequential story word as the commit message
5. Git operations are automated with retry logic and error handling

### Configuration
- Uses a `Config` class (config file not in repo)
- Database configuration via `Config` object
- Git repository path configured in `$config->post_path_journal`
- **STORY FILE**: Critical master story file path in `$config->storyFile` - contains the complete narrative that drives all commit messages

## Development Notes

### No Build System
This is a traditional PHP application with no build tools, package managers, or compilation steps. Files are served directly by the web server.

### Git Integration
The application automatically commits and pushes changes to a git repository. The `TempOSpooner` class handles all git operations with retry logic for reliability.

### Template System
Uses a simple PHP template system where variables are extracted into local scope. Templates are in `templates/` directory with `.tpl.php` extensions.

### Debugging
Set `?debug=N` parameter in URLs to enable debug output (where N is debug level 1-6). Higher levels show more verbose output.

### Authentication
All pages require authentication except login. Authentication state is managed through `Auth\IsLoggedIn` class and PHP sessions.

### File Structure for Posts
Posts are saved as Markdown files with YAML frontmatter in a date-based directory structure: `YYYY/MM/DD{url-title}.md`