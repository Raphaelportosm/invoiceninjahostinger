'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';
const RESOURCES = {
  "version.json": "27abc97e9c76cf112b697fa080c304b5",
"favicon.ico": "51636d3a390451561744c42188ccd628",
"favicon.png": "dca91c54388f52eded692718d5a98b8b",
"assets/fonts/MaterialIcons-Regular.otf": "4e6447691c9509f7acdbf8a931a85ca1",
"assets/NOTICES": "9eb7e2eb2888ea5bae5f536720db37cd",
"assets/assets/images/icon.png": "090f69e23311a4b6d851b3880ae52541",
"assets/assets/images/google_logo.png": "0f118259ce403274f407f5e982e681c3",
"assets/assets/images/logo_light.png": "e5f46d5a78e226e7a9553d4ca6f69219",
"assets/assets/images/payment_types/laser.png": "b4e6e93dd35517ac429301119ff05868",
"assets/assets/images/payment_types/discover.png": "6c0a386a00307f87db7bea366cca35f5",
"assets/assets/images/payment_types/mastercard.png": "6f6cdc29ee2e22e06b1ac029cb52ef71",
"assets/assets/images/payment_types/amex.png": "c49a4247984b3732a4af50a3390aa978",
"assets/assets/images/payment_types/ach.png": "7433f0aff779dc98a649b7a2daf777cf",
"assets/assets/images/payment_types/carteblanche.png": "d936e11fa3884b8c9f1bd5c914be8629",
"assets/assets/images/payment_types/other.png": "d936e11fa3884b8c9f1bd5c914be8629",
"assets/assets/images/payment_types/visa.png": "3ddc4a4d25c946e8ad7e6998f30fd4e3",
"assets/assets/images/payment_types/maestro.png": "e533b92bfb50339fdbfa79e3dfe81f08",
"assets/assets/images/payment_types/jcb.png": "07e0942d16c5592118b72e74f2f7198c",
"assets/assets/images/payment_types/solo.png": "2030c3ccaccf5d5e87916a62f5b084d6",
"assets/assets/images/payment_types/switch.png": "4fa11c45327f5fdc20205821b2cfd9cc",
"assets/assets/images/payment_types/paypal.png": "8e06c094c1871376dfea1da8088c29d1",
"assets/assets/images/payment_types/unionpay.png": "7002f52004e0ab8cc0b7450b0208ccb2",
"assets/assets/images/payment_types/dinerscard.png": "06d85186ba858c18ab7c9caa42c92024",
"assets/assets/images/logo_dark.png": "a233ed1d4d0f7414bf97a9a10f11fb0a",
"assets/FontManifest.json": "cf3c681641169319e61b61bd0277378f",
"assets/AssetManifest.json": "38d9aea341601f3a5c6fa7b5a1216ea5",
"assets/packages/material_design_icons_flutter/lib/fonts/materialdesignicons-webfont.ttf": "174c02fc4609e8fc4389f5d21f16a296",
"icons/Icon-192.png": "bb1cf5f6982006952211c7c8404ffbed",
"icons/Icon-512.png": "0f9aff01367f0a0c69773d25ca16ef35",
"main.dart.js": "4b982138f175597df211146084e39b66",
"manifest.json": "ef43d90e57aa7682d7e2cfba2f484a40",
"/": "3debb9cbb687369f006a776cc7ab525b"
};

// The application shell files that are downloaded before a service worker can
// start.
const CORE = [
  "/",
"main.dart.js",
"assets/NOTICES",
"assets/AssetManifest.json",
"assets/FontManifest.json"];
// During install, the TEMP cache is populated with the application shell files.
self.addEventListener("install", (event) => {
  self.skipWaiting();
  return event.waitUntil(
    caches.open(TEMP).then((cache) => {
      return cache.addAll(
        CORE.map((value) => new Request(value, {'cache': 'reload'})));
    })
  );
});

// During activate, the cache is populated with the temp files downloaded in
// install. If this service worker is upgrading from one with a saved
// MANIFEST, then use this to retain unchanged resource files.
self.addEventListener("activate", function(event) {
  return event.waitUntil(async function() {
    try {
      var contentCache = await caches.open(CACHE_NAME);
      var tempCache = await caches.open(TEMP);
      var manifestCache = await caches.open(MANIFEST);
      var manifest = await manifestCache.match('manifest');
      // When there is no prior manifest, clear the entire cache.
      if (!manifest) {
        await caches.delete(CACHE_NAME);
        contentCache = await caches.open(CACHE_NAME);
        for (var request of await tempCache.keys()) {
          var response = await tempCache.match(request);
          await contentCache.put(request, response);
        }
        await caches.delete(TEMP);
        // Save the manifest to make future upgrades efficient.
        await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
        return;
      }
      var oldManifest = await manifest.json();
      var origin = self.location.origin;
      for (var request of await contentCache.keys()) {
        var key = request.url.substring(origin.length + 1);
        if (key == "") {
          key = "/";
        }
        // If a resource from the old manifest is not in the new cache, or if
        // the MD5 sum has changed, delete it. Otherwise the resource is left
        // in the cache and can be reused by the new service worker.
        if (!RESOURCES[key] || RESOURCES[key] != oldManifest[key]) {
          await contentCache.delete(request);
        }
      }
      // Populate the cache with the app shell TEMP files, potentially overwriting
      // cache files preserved above.
      for (var request of await tempCache.keys()) {
        var response = await tempCache.match(request);
        await contentCache.put(request, response);
      }
      await caches.delete(TEMP);
      // Save the manifest to make future upgrades efficient.
      await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
      return;
    } catch (err) {
      // On an unhandled exception the state of the cache cannot be guaranteed.
      console.error('Failed to upgrade service worker: ' + err);
      await caches.delete(CACHE_NAME);
      await caches.delete(TEMP);
      await caches.delete(MANIFEST);
    }
  }());
});

// The fetch handler redirects requests for RESOURCE files to the service
// worker cache.
self.addEventListener("fetch", (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  var origin = self.location.origin;
  var key = event.request.url.substring(origin.length + 1);
  if (key.indexOf('?v=') != -1) {
    key = key.split('?v=')[0];
  }
  if (event.request.url == origin || event.request.url.startsWith(origin + '/#') || key == '') {
    key = '/';
  }
  // If the URL is not the RESOURCE list then return to signal that the
  // browser should take over.
  if (!RESOURCES[key]) {
    return;
  }
  if (key == '/') {
    return onlineFirst(event);
  }
  event.respondWith(caches.open(CACHE_NAME)
    .then((cache) =>  {
      return cache.match(event.request).then((response) => {
        // Either respond with the cached resource, or perform a fetch and
        // lazily populate the cache.
        return response || fetch(event.request).then((response) => {
          cache.put(event.request, response.clone());
          return response;
        });
      })
    })
  );
});

self.addEventListener('message', (event) => {
  // SkipWaiting can be used to immediately activate a waiting service worker.
  // This will also require a page refresh triggered by the main worker.
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
    return;
  }
  if (event.data === 'downloadOffline') {
    downloadOffline();
    return;
  }
});

// Download offline will check the RESOURCES for all files not in the cache
// and populate them.
async function downloadOffline() {
  var resources = [];
  var contentCache = await caches.open(CACHE_NAME);
  var currentContent = {};
  for (var request of await contentCache.keys()) {
    var key = request.url.substring(origin.length + 1);
    if (key == "") {
      key = "/";
    }
    currentContent[key] = true;
  }
  for (var resourceKey of Object.keys(RESOURCES)) {
    if (!currentContent[resourceKey]) {
      resources.push(resourceKey);
    }
  }
  return contentCache.addAll(resources);
}

// Attempt to download the resource online before falling back to
// the offline cache.
function onlineFirst(event) {
  return event.respondWith(
    fetch(event.request).then((response) => {
      return caches.open(CACHE_NAME).then((cache) => {
        cache.put(event.request, response.clone());
        return response;
      });
    }).catch((error) => {
      return caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request).then((response) => {
          if (response != null) {
            return response;
          }
          throw error;
        });
      });
    })
  );
}
