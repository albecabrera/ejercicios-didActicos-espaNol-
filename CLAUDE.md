# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a repository for educational exercises in Spanish ("ejercicios didácticos español"). It contains interactive HTML-based learning games and exercises for Spanish language learners.

## Repository Structure

- `index.html` - Main landing page listing all available exercises
  - Exercise catalog with cards showing title, description, level, and topics
  - QR code generation system for easy sharing
  - Modal interface for displaying QR codes and shareable links
- `ejercicios/` - Contains individual HTML exercise files
  - Each exercise is a self-contained HTML file with embedded CSS and JavaScript
  - No build process or external dependencies required

## Current Exercises

### Mi Barrio (Madrid Adventure)
`ejercicios/mi_barrio_spiel.html` - An interactive vocabulary game teaching Spanish words and phrases through a story-based journey around Madrid.

**Features:**
- 10 levels with scenario-based learning
- Topics: directions, landmarks, food, shopping, transportation
- Text-to-speech functionality using Web Speech API
- Progress tracking with points and stars
- Responsive design for mobile and desktop

**Technical Details:**
- Pure HTML/CSS/JavaScript (no frameworks)
- Uses Web Speech API for Spanish pronunciation (`speechSynthesis`, `es-ES` locale)
- Game state managed in JavaScript object
- Gradient-based UI design with animations

## Development

### Running Exercises
Open HTML files directly in a web browser. No server or build process required.

### Adding New Exercises
1. Create a self-contained HTML file in the `ejercicios/` directory
2. Follow the existing pattern: embedded styles and scripts, responsive design, interactive elements
3. Add the exercise to the `exercises` array in `index.html`:
   ```javascript
   {
       id: 'unique-id',
       icon: '🎯',
       title: 'Exercise Title',
       description: 'Detailed description of the exercise',
       file: 'ejercicios/filename.html',
       level: 'A1-A2', // CEFR level
       topics: ['Topic1', 'Topic2'],
       language: 'DE → ES' // Interface → Learning language
   }
   ```

### GitHub Pages Deploy
- Update `GITHUB_USERNAME` constant in `index.html` (line 228) before deploying
- The QR codes automatically generate URLs based on: `https://[username].github.io/ejercicios_didacticos/`
- Enable GitHub Pages in repository settings (Settings → Pages → Source: main branch)
- Access via: `https://[username].github.io/ejercicios_didacticos/`

## Language Context
- Interface language: German (Deutsch)
- Target learning language: Spanish (Español)
- Code comments: Should be in German or Spanish for consistency
