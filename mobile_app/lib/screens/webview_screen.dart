import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:io' show Platform;
import 'package:webview_flutter/webview_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
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
  late final WebViewController _controller;
  bool _isLoading = true;
  String _currentUrl = '';
  bool _canGoBack = false;
  bool _canGoForward = false;

  @override
  void initState() {
    super.initState();
    _currentUrl = widget.url;
    
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..enableZoom(true)
      // Enhanced settings for stability
      ..setUserAgent('Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36')
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
              _currentUrl = url;
            });
          },
          onPageFinished: (String url) async {
            setState(() {
              _isLoading = false;
              _currentUrl = url;
            });
            
            // Update navigation state
            final canGoBack = await _controller.canGoBack();
            final canGoForward = await _controller.canGoForward();
            setState(() {
              _canGoBack = canGoBack;
              _canGoForward = canGoForward;
            });
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint('❌ WebView error: ${error.description} (code: ${error.errorCode})');
            setState(() {
              _isLoading = false;
            });
            
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
            debugPrint('❌ HTTP error: ${error.response?.statusCode}');
            // Don't show snackbar for HTTP errors - let page handle it
          },
          onNavigationRequest: (NavigationRequest request) {
            // Allow all navigation within the webview
            debugPrint('🔗 Navigation to: ${request.url}');
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  Future<void> _openInBrowser() async {
    try {
      final urlToOpen = _currentUrl.isNotEmpty ? _currentUrl : widget.url;
      final Uri url = Uri.parse(urlToOpen);
      
      debugPrint('🌐 Attempting to open URL in browser: $urlToOpen');
      
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
          debugPrint('✅ Launched with externalApplication mode');
        } catch (e) {
          debugPrint('⚠️ externalApplication failed: $e');
        }
        
        // Method 2: Try platformDefault if method 1 failed
        if (!launched) {
          try {
            launched = await launchUrl(
              url,
              mode: LaunchMode.platformDefault,
            );
            debugPrint('✅ Launched with platformDefault mode');
          } catch (e) {
            debugPrint('⚠️ platformDefault failed: $e');
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
            debugPrint('✅ Launched with inAppBrowserView mode');
          } catch (e) {
            debugPrint('⚠️ inAppBrowserView failed: $e');
          }
        }
        
        // Method 4: Last resort - try basic launch
        if (!launched) {
          try {
            launched = await launchUrl(url);
            debugPrint('✅ Launched with basic launchUrl');
          } catch (e) {
            debugPrint('⚠️ basic launchUrl failed: $e');
          }
        }
      } else {
        // For other platforms (desktop, etc.)
        launched = await launchUrl(url, mode: LaunchMode.platformDefault);
      }
      
      if (launched) {
        debugPrint('✅ Successfully opened URL in external browser');
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
        debugPrint('❌ All launch methods failed');
        _showErrorSnackBar(
          'Unable to open external browser. You can continue browsing here.',
        );
      }
    } catch (e) {
      debugPrint('❌ Error opening browser: $e');
      _showErrorSnackBar('Could not open browser. You can continue browsing in-app.');
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
    await _controller.reload();
  }

  Future<void> _goBack() async {
    if (await _controller.canGoBack()) {
      await _controller.goBack();
    }
  }

  Future<void> _goForward() async {
    if (await _controller.canGoForward()) {
      await _controller.goForward();
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
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
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
                    color: _canGoBack ? AppTheme.accentColor : Colors.grey.shade600,
                    size: 24,
                  ),
                  onPressed: _canGoBack ? _goBack : null,
                  tooltip: 'Back',
                ),
                IconButton(
                  icon: Icon(
                    Icons.arrow_forward_rounded,
                    color: _canGoForward ? AppTheme.accentColor : Colors.grey.shade600,
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
                  onPressed: () {
                    _controller.loadRequest(Uri.parse(widget.url));
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
