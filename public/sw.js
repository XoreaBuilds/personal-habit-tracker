/**
 * sw.js - Service Worker for PWA
 * 
 * This script runs in the background and is required for the app to be
 * "installable" on mobile devices and desktop.
 */
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
    // Basic service worker to enable installability
});
