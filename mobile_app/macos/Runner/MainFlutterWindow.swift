import Cocoa
import FlutterMacOS

class MainFlutterWindow: NSWindow {
  // Retain the Apple inventory-search bridge for the lifetime of the window.
  private var siriInventoryChannel: FlutterMethodChannel?

  override func awakeFromNib() {
    let flutterViewController = FlutterViewController()
    let windowFrame = self.frame
    self.contentViewController = flutterViewController
    self.setFrame(windowFrame, display: true)

    RegisterGeneratedPlugins(registry: flutterViewController)

    let registrar = flutterViewController.registrar(
      forPlugin: "AngelGranitesInventorySearch"
    )
    let channel = FlutterMethodChannel(
      name: "com.angelgranites.app/siri_inventory",
      binaryMessenger: registrar.messenger
    )
    channel.setMethodCallHandler { call, result in
      guard call.method == "takePendingSearch" else {
        result(FlutterMethodNotImplemented)
        return
      }
      result(PendingInventorySearch.take())
    }
    siriInventoryChannel = channel

    super.awakeFromNib()
  }
}
