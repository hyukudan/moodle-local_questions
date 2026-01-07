/**
 * ESLint flat config for Moodle AMD modules.
 * @see https://eslint.org/docs/latest/use/configure/configuration-files
 */
import js from '@eslint/js';
import globals from 'globals';

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.amd,
                // Moodle globals
                M: 'readonly',
                Y: 'readonly',
                // Bootstrap 5 global (loaded by Moodle)
                bootstrap: 'readonly',
            }
        },
        rules: {
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            'no-console': 'off',
            'semi': ['error', 'always'],
            'quotes': ['warn', 'single', { avoidEscape: true }],
            'indent': ['warn', 4],
            'no-trailing-spaces': 'warn',
            'eol-last': 'warn'
        }
    }
];
