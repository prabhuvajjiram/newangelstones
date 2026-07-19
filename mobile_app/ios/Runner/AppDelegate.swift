import Flutter
import UIKit
import Firebase

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
  // Retain the channel for the lifetime of the implicit Flutter engine. The
  // stable App Shortcut and the gated Apple 27 search schema share this bridge.
  private var siriInventoryChannel: FlutterMethodChannel?

  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    FirebaseApp.configure()
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  func didInitializeImplicitFlutterEngine(_ engineBridge: FlutterImplicitEngineBridge) {
    GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)

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
  }
}
