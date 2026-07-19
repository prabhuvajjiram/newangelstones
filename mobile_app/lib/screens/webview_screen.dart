import 'dart:async';
import 'dart:io' show Platform;

import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart'
    show TargetPlatform, defaultTargetPlatform, kDebugMode, kIsWeb;
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import 'package:webview_flutter_wkwebview/webview_flutter_wkwebview.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/security_config.dart';
import '../theme/app_theme.dart';

class WebViewScreen extends StatefulWidget {
  final String url;
  final String title;

  const WebViewScreen({
    super.key,
    required this.url,
    required this.title,
  });

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  WebViewController? _controller;
  bool _isLoading = true;
  String _currentUrl = '';
  bool _canGoBack = false;
  bool _canGoForward = false;

  @override
  void initState() {
    super.initState();
    _currentUrl = widget.url;

    // webview_flutter has native implementations for Android and Apple
    // platforms in this project, but not Windows/Linux. Keep those desktop
    // targets usable by offering the trusted URL in the system browser.
    if (kIsWeb ||
        defaultTargetPlatform == TargetPlatform.windows ||
        defaultTargetPlatform == TargetPlatform.linux) {
      _isLoading = false;
      return;
    }

    // Platform-specific initialization to disable ORB
    late final PlatformWebViewControllerCreationParams params;
    if (WebViewPlatform.instance is WebKitWebViewPlatform) {
      params = WebKitWebViewControllerCreationParams(
        allowsInlineMediaPlayback: true,
        mediaTypesRequiringUserAction: const <PlaybackMediaTypes>{},
      );
    } else if (WebViewPlatform.instance is AndroidWebViewPlatform) {
      params = AndroidWebViewControllerCreationParams();
    } else {
      params = const PlatformWebViewControllerCreationParams();
    }

    final controller = WebViewController.fromPlatformCreationParams(params);
    _controller = controller;
    controller
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..enableZoom(true)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            _debugLogUrl('Page started loading', url);
            if (mounted) {
              setState(() {
                _isLoading = true;
                _currentUrl = url;
              });
            }
          },
          onPageFinished: (String url) async {
            if (mounted) {
              setState(() {
                _isLoading = false;
                _currentUrl = url;
              });
            }

            _debugLogUrl('Page loaded successfully', url);

            // Update navigation state
            final canGoBack = await controller.canGoBack();
            final canGoForward = await controller.canGoForward();
            if (mounted) {
              setState(() {
                _canGoBack = canGoBack;
                _canGoForward = canGoForward;
              });
            }
          },
          onWebResourceError: (WebResourceError error) {
            if (kDebugMode) {
              debugPrint(
                'WebView error: ${error.description} '
                '(code: ${error.errorCode}, type: ${error.errorType})',
              );
            }

            // Ignore ORB errors - they're security restrictions that don't prevent page rendering
            if (error.description.contains('ERR_BLOCKED_BY_ORB') ||
                error.description.contains('BLOCKED_BY_ORB')) {
              debugPrint(
                  '🔒 ORB error detected - ignoring (security restriction)');
              return;
            }

            if (mounted) {
              setState(() {
                _isLoading = false;
              });
            }

            // Only show error for critical failures (not for images, scripts, etc.)
            if (error.errorType == WebResourceErrorType.unknown ||
                error.errorType == WebResourceErrorType.hostLookup ||
                error.errorType == WebResourceErrorType.timeout ||
                error.errorType == WebResourceErrorType.connect) {
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Network error: ${error.description}'),
                    backgroundColor: Colors.orange.shade800,
                    behavior: SnackBarBehavior.floating,
                    action: SnackBarAction(
                      label: 'RETRY',
                      textColor: Colors.white,
                      onPressed: () => _refresh(),
                    ),
                  ),
                );
              }
            }
          },
          onHttpError: (HttpResponseError error) {
            if (kDebugMode) {
              debugPrint('WebView HTTP error: ${error.response?.statusCode}');
            }
            // Don't show snackbar for HTTP errors - let page handle it
          },
          onNavigationRequest: (NavigationRequest request) {
            _debugLogUrl('Navigation', request.url);
            if (!request.isMainFrame ||
                SecurityConfig.isAllowedWebViewUrl(request.url)) {
              return NavigationDecision.navigate;
            }

            final uri = Uri.tryParse(request.url);
            if (uri != null &&
                (uri.scheme == 'https' ||
                    uri.scheme == 'mailto' ||
                    uri.scheme == 'tel')) {
              unawaited(_openExternalNavigation(uri));
            } else {
              _showErrorSnackBar('Blocked an unsupported link.');
            }
            return NavigationDecision.prevent;
          },
        ),
      );

    // Load the URL - errors will be caught in error handler
    controller.loadRequest(
      Uri.parse(widget.url),
      headers: {
        'Accept':
            'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.9',
        'Cache-Control': 'no-cache',
        'Pragma': 'no-cache',
      },
    );

    // Platform-specific configuration for Android
    if (controller.platform is AndroidWebViewController) {
      AndroidWebViewController.enableDebugging(kDebugMode);
      (controller.platform as AndroidWebViewController)
        ..setMediaPlaybackRequiresUserGesture(false)
        ..setGeolocationPermissionsPromptCallbacks(
          onShowPrompt: (request) async {
            return const GeolocationPermissionsResponse(
              allow: false,
              retain: false,
            );
          },
        );
    }
  }

  void _debugLogUrl(String event, String url) {
    if (!kDebugMode) return;
    debugPrint('$event: ${SecurityConfig.redactUrlForLogging(url)}');
  }

  Future<void> _openExternalNavigation(Uri uri) async {
    try {
      _debugLogUrl('Opening externally', uri.toString());
      final launched = await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      );
      if (!launched) {
        _showErrorSnackBar('Unable to open this link in your browser.');
      }
    } catch (_) {
      _showErrorSnackBar('Unable to open this link in your browser.');
    }
  }

  Future<void> _openInBrowser() async {
    try {
      final urlToOpen = _currentUrl.isNotEmpty ? _currentUrl : widget.url;
      final Uri url = Uri.parse(urlToOpen);

      _debugLogUrl('Opening in browser', urlToOpen);

      bool launched = false;

      if (kIsWeb) {
        // For web platform
        launched = await launchUrl(url, mode: LaunchMode.platformDefault);
      } else if (Platform.isIOS) {
        // For iOS - try multiple methods
        launched = await launchUrl(url, mode: LaunchMode.externalApplication);

        if (!launched) {
          launched = await launchUrl(url, mode: LaunchMode.platformDefault);
        }
      } else if (Platform.isAndroid) {
        // For Android - try multiple approaches to handle emulator and real devices

        // Method 1: Try externalApplication mode
        try {
          launched = await launchUrl(
            url,
            mode: LaunchMode.externalApplication,
          );
          if (kDebugMode) {
            debugPrint('Launched with externalApplication mode');
          }
        } catch (e) {
          if (kDebugMode) debugPrint('externalApplication failed: $e');
        }

        // Method 2: Try platformDefault if method 1 failed
        if (!launched) {
          try {
            launched = await launchUrl(
              url,
              mode: LaunchMode.platformDefault,
            );
            if (kDebugMode) debugPrint('Launched with platformDefault mode');
          } catch (e) {
            if (kDebugMode) debugPrint('platformDefault failed: $e');
          }
        }

        // Method 3: Try with webViewConfiguration for Android
        if (!launched) {
          try {
            launched = await launchUrl(
              url,
              mode: LaunchMode.inAppBrowserView,
              webViewConfiguration: const WebViewConfiguration(
                enableJavaScript: true,
                enableDomStorage: true,
              ),
            );
            if (kDebugMode) debugPrint('Launched with inAppBrowserView mode');
          } catch (e) {
            if (kDebugMode) debugPrint('inAppBrowserView failed: $e');
          }
        }

        // Method 4: Last resort - try basic launch
        if (!launched) {
          try {
            launched = await launchUrl(url);
            if (kDebugMode) debugPrint('Launched with basic launchUrl');
          } catch (e) {
            if (kDebugMode) debugPrint('basic launchUrl failed: $e');
          }
        }
      } else {
        // For other platforms (desktop, etc.)
        launched = await launchUrl(url, mode: LaunchMode.platformDefault);
      }

      if (launched) {
        if (kDebugMode) {
          debugPrint('Successfully opened URL in external browser');
        }
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Opening in browser...'),
              duration: Duration(seconds: 2),
              behavior: SnackBarBehavior.floating,
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        if (kDebugMode) debugPrint('All browser launch methods failed');
        _showErrorSnackBar(
          'Unable to open external browser. You can continue browsing here.',
        );
      }
    } catch (e) {
      if (kDebugMode) debugPrint('Error opening browser: $e');
      _showErrorSnackBar(
          'Could not open browser. You can continue browsing in-app.');
    }
  }

  void _showErrorSnackBar(String message) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(message),
          backgroundColor: Colors.red.shade800,
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 3),
          action: SnackBarAction(
            label: 'OK',
            textColor: Colors.white,
            onPressed: () {},
          ),
        ),
      );
    }
  }

  Future<void> _refresh() async {
    final controller = _controller;
    if (controller == null) {
      await _openInBrowser();
      return;
    }
    await controller.reload();
  }

  Future<void> _goBack() async {
    final controller = _controller;
    if (controller != null && await controller.canGoBack()) {
      await controller.goBack();
    }
  }

  Future<void> _goForward() async {
    final controller = _controller;
    if (controller != null && await controller.canGoForward()) {
      await controller.goForward();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
          ),
        ),
        backgroundColor: AppTheme.primaryColor,
        foregroundColor: Colors.white,
        elevation: 2,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, size: 22),
            onPressed: _refresh,
            tooltip: 'Refresh',
          ),
          IconButton(
            icon: const Icon(Icons.open_in_browser, size: 22),
            onPressed: _openInBrowser,
            tooltip: 'Open in Browser',
          ),
        ],
      ),
      body: _controller == null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.open_in_browser_rounded,
                      color: AppTheme.accentColor,
                      size: 64,
                    ),
                    const SizedBox(height: 20),
                    Text(
                      '${widget.title} opens securely in your default browser on this device.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 16),
                    ),
                    const SizedBox(height: 20),
                    FilledButton.icon(
                      onPressed: _openInBrowser,
                      icon: const Icon(Icons.open_in_new_rounded),
                      label: const Text('Open in Browser'),
                    ),
                  ],
                ),
              ),
            )
          : Stack(
              children: [
                WebViewWidget(controller: _controller!),
                if (_isLoading)
                  Container(
                    color: Colors.white,
                    child: const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          CircularProgressIndicator(
                            color: AppTheme.accentColor,
                          ),
                          SizedBox(height: 16),
                          Text(
                            'Loading...',
                            style: TextStyle(
                              color: AppTheme.textSecondary,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: AppTheme.primaryColor,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.1),
              blurRadius: 4,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                IconButton(
                  icon: Icon(
                    Icons.arrow_back_rounded,
                    color: _canGoBack
                        ? AppTheme.accentColor
                        : Colors.grey.shade600,
                    size: 24,
                  ),
                  onPressed: _canGoBack ? _goBack : null,
                  tooltip: 'Back',
                ),
                IconButton(
                  icon: Icon(
                    Icons.arrow_forward_rounded,
                    color: _canGoForward
                        ? AppTheme.accentColor
                        : Colors.grey.shade600,
                    size: 24,
                  ),
                  onPressed: _canGoForward ? _goForward : null,
                  tooltip: 'Forward',
                ),
                IconButton(
                  icon: const Icon(
                    Icons.home_rounded,
                    color: AppTheme.accentColor,
                    size: 24,
                  ),
                  onPressed: _controller == null
                      ? _openInBrowser
                      : () {
                          _controller!.loadRequest(Uri.parse(widget.url));
                        },
                  tooltip: 'Home',
                ),
                IconButton(
                  icon: const Icon(
                    Icons.open_in_new_rounded,
                    color: AppTheme.accentColor,
                    size: 24,
                  ),
                  onPressed: _openInBrowser,
                  tooltip: 'Open in Browser',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
