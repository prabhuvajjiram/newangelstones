import Flutter
import UIKit
import Firebase

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
#if IOS27_SIRI_ENABLED
  // Enable together with InventorySearchIntent.swift after Xcode 27 is
  // accepted for production App Store submissions.
  private var siriInventoryChannel: FlutterMethodChannel?
#endif

  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    FirebaseApp.configure()
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  func didInitializeImplicitFlutterEngine(_ engineBridge: FlutterImplicitEngineBridge) {
    GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)

#if IOS27_SIRI_ENABLED
    let channel = FlutterMethodChannel(
      name: "com.angelgranites.app/siri_inventory",
      binaryMessenger: engineBridge.applicationRegistrar.messenger()
    )
    channel.setMethodCallHandler { call, result in
      guard call.method == "takePendingSearch" else {
        result(FlutterMethodNotImplemented)
        return
      }
      result(PendingInventorySearch.take())
    }
    siriInventoryChannel = channel
#endif
  }
}
