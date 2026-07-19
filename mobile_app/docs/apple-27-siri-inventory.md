# Apple 27 Siri inventory search

Angel Granites exposes inventory search to Siri on both iOS and macOS through
App Intents.

## What is active now

`CheckInventoryAvailabilityIntent` is available on iOS 16 or later and macOS
13 or later. `InventoryAvailabilityEntity` and its `EntityStringQuery` resolve
the variable portion of each phrase against the live inventory service:

- “Siri, check AG-298 in Angel Granites.”
- “Siri, is a heart headstone available in Angel Granites?”
- “Siri, search Angel Granites for 0-8 thickness.”
- “Siri, check 4-0 x 0-8 x 2-4 in Angel Granites.”

The App Entity is important because Apple only permits an App Entity or App
Enum—not an arbitrary String—as a variable inside an App Shortcut invocation
phrase. The string query accepts the person's free-form words, performs the
server-backed inventory match, and returns a displayable entity containing the
live spoken answer.

The intent reads the live inventory feed and responds without needing to open
the Flutter interface. If live inventory is temporarily unavailable, Siri
returns a friendly retry message instead of exposing a network error.

## Apple 27 system search schema

`SearchAngelGranitesInventoryIntent` adopts Apple's 27 system
`searchInApp` schema. Siri can hand a free-form search to the app, which opens
the Stock tab and reuses the same Flutter inventory search and ranking logic.
This is the one-step route for requests such as “Show AG-298 in Angel
Granites.”

The schema is preserved in source behind `APPLE27_SIRI_ENABLED`. Debug builds
activate it automatically when the selected SDK is iOS 27, iOS Simulator 27,
or macOS 27. Xcode 26 builds do not receive the flag, and Release/Profile builds
remain unaffected.

After installing Xcode 27 beta alongside stable Xcode:

1. Open the iOS or macOS Runner project in Xcode 27 beta.
2. Select the **Debug** configuration and an Apple 27 SDK destination.
3. Confirm `APPLE27_SIRI_ENABLED` appears under **Swift Active Compilation
   Conditions**; the project supplies it conditionally.
4. Build on an iOS 27 or macOS 27 test device.
5. Test the intent in Xcode's App Intents testing UI, then Shortcuts, Spotlight,
   and Siri.

Do not add the flag to Release until Apple accepts submissions built with that
Xcode version and the final API has been revalidated. Keeping the beta flag in
Debug means current App Store releases are unaffected.

Both Runner targets compile the shared implementation from
`ios/Runner/InventorySearchIntent.swift`. Keep behavior changes in that one
file so iPhone and Mac do not drift.

## Apple references

- [App Intents](https://developer.apple.com/documentation/AppIntents/app-intents)
- [System search-in-app schema](https://developer.apple.com/documentation/appintents/appschema/systemintent/searchinapp)
- [Build intelligent Siri experiences with App Schemas](https://developer.apple.com/videos/play/wwdc2026/240/)
- [Explore new advances in App Intents](https://developer.apple.com/videos/play/wwdc2026/343/)
