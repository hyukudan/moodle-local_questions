#!/usr/bin/env node
/**
 * Build script for local_questions AMD modules.
 *
 * Minifies JS files from amd/src/ to amd/build/ following Moodle conventions.
 *
 * Usage:
 *   npm run build         - Build all AMD modules
 *   npm run build:watch   - Watch for changes and rebuild
 */

import esbuild from 'esbuild';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const srcDir = path.join(__dirname, 'amd', 'src');
const buildDir = path.join(__dirname, 'amd', 'build');

// Ensure build directory exists.
if (!fs.existsSync(buildDir)) {
    fs.mkdirSync(buildDir, { recursive: true });
}

// Get all JS files from src directory.
const getSourceFiles = () => {
    if (!fs.existsSync(srcDir)) {
        console.error('Error: amd/src directory not found');
        process.exit(1);
    }
    return fs.readdirSync(srcDir)
        .filter(file => file.endsWith('.js'))
        .map(file => path.join(srcDir, file));
};

// Build configuration.
const buildOptions = {
    entryPoints: getSourceFiles(),
    outdir: buildDir,
    minify: true,
    sourcemap: false,
    // Keep AMD format - don't bundle or transform module format.
    format: 'iife',
    // Preserve define() calls for Moodle AMD loader.
    banner: {
        js: '// Minified by local_questions build script.'
    },
    // Output with .min.js extension as Moodle expects.
    outExtension: { '.js': '.min.js' },
    // Don't bundle - keep external dependencies.
    bundle: false,
};

// Check for watch mode.
const isWatch = process.argv.includes('--watch');

const build = async () => {
    try {
        if (isWatch) {
            // Watch mode.
            const ctx = await esbuild.context(buildOptions);
            await ctx.watch();
            console.log('Watching for changes in amd/src/...');
        } else {
            // Single build.
            const result = await esbuild.build(buildOptions);
            const files = getSourceFiles();
            console.log(`Built ${files.length} AMD module(s):`);
            files.forEach(file => {
                const basename = path.basename(file, '.js');
                console.log(`  ✓ ${basename}.js → ${basename}.min.js`);
            });
        }
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
};

build();
