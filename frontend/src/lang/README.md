# Frontend Translation System

This project uses Vue i18n for internationalization, supporting English and Khmer languages.

## File Structure

```
frontend/src/lang/
├── index.js      # Translation configuration and locale management
├── en.json       # English translations
└── km.json       # Khmer translations
```

## Usage in Vue Components

### 1. Import and Setup

```javascript
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
```

### 2. Using Translations in Templates

```vue
<template>
  <div>
    <h1>{{ t('users') }}</h1>
    <button>{{ t('save') }}</button>
    <p>{{ t('welcome_message', { name: userName }) }}</p>
  </div>
</template>
```

### 3. Using Translations in JavaScript

```javascript
const message = t('error_occurred');
const welcome = t('welcome_user', { name: 'John' });
```

## Available Translation Keys

### Common UI Elements
- `save` - Save button
- `edit` - Edit button
- `delete` - Delete button
- `cancel` - Cancel button
- `close` - Close button
- `loading` - Loading text
- `search` - Search placeholder

### User Management
- `users` - Users page title
- `create_user` - Create user button
- `edit_user` - Edit user modal title
- `user_level` - User level field label
- `super_admin` - Super admin role
- `admin` - Admin role
- `regular_user` - Regular user role

### Booking System
- `bookings` - Bookings page title
- `create_booking` - Create booking
- `meeting_title` - Meeting title field
- `start_datetime` - Start date & time
- `end_datetime` - End date & time

### Status Messages
- `status_pending` - Pending status
- `status_approved` - Approved status
- `status_rejected` - Rejected status
- `status_completed` - Completed status

## Adding New Translations

1. Add the key-value pair to both `en.json` and `km.json`
2. Use the `t()` function in your Vue components
3. Test in both English and Khmer locales

### Example

**en.json:**
```json
{
  "new_feature": "New Feature",
  "welcome_back": "Welcome back, {name}!"
}
```

**km.json:**
```json
{
  "new_feature": "មុខងារថ្មី",
  "welcome_back": "សូមស្វាគមន៍មកវិញ {name}!"
}
```

**Usage:**
```vue
<template>
  <div>
    <h2>{{ t('new_feature') }}</h2>
    <p>{{ t('welcome_back', { name: user.name }) }}</p>
  </div>
</template>
```

## Language Switching

The app includes a locale switcher component that allows users to change between English and Khmer. The selected language is saved in localStorage.

## Best Practices

1. Use descriptive, consistent key names
2. Group related translations together
3. Use interpolation for dynamic content
4. Test translations in both languages
5. Keep keys in alphabetical order within categories</content>
<parameter name="filePath">/Users/ratana/Documents/laravel-project/back-laravel/frontend/src/lang/README.md