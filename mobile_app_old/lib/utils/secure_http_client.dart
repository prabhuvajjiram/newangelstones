import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../config/security_config.dart';

/// Secure HTTP client with built-in security measures
class SecureHttpClient {
  static final SecureHttpClient _instance = SecureHttpClient._internal();
  factory SecureHttpClient() => _instance;
  SecureHttpClient._internal();

  late final http.Client _client;
  
  /// Initialize the secure HTTP client
  void initialize() {
    _client = http.Client();
  }

  /// Make a secure GET request
  Future<http.Response> secureGet(
    String url, {
    Map<String, String>? additionalHeaders,
    Duration? timeout,
  }) async {
    return _makeSecureRequest(
      'GET',
      url,
      additionalHeaders: additionalHeaders,
      timeout: timeout,
    );
  }

  /// Make a secure POST request
  Future<http.Response> securePost(
    String url, {
    Map<String, String>? additionalHeaders,
    Object? body,
    Encoding? encoding,
    Duration? timeout,
  }) async {
    return _makeSecureRequest(
      'POST',
      url,
      additionalHeaders: additionalHeaders,
      body: body,
      encoding: encoding,
      timeout: timeout,
    );
  }

  /// Internal method to make secure requests
  Future<http.Response> _makeSecureRequest(
    String method,
    String url, {
    Map<String, String>? additionalHeaders,
    Object? body,
    Encoding? encoding,
    Duration? timeout,
  }) async {
    // Validate URL
    if (!SecurityConfig.isValidUrl(url)) {
      throw SecurityException('Invalid or unsafe URL: $url');
    }

    // Prepare headers
    final headers = <String, String>{
      ...SecurityConfig.getSecurityHeaders(),
      if (additionalHeaders != null) ...additionalHeaders,
    };

    // Add Content-Type for POST requests
    if (method == 'POST' && body != null) {
      headers['Content-Type'] = 'application/json; charset=utf-8';
    }

    try {
      final uri = Uri.parse(url);
      final request = http.Request(method, uri);
      request.headers.addAll(headers);
      
      if (body != null) {
        if (body is String) {
          request.body = body;
        } else if (body is List<int>) {
          request.bodyBytes = body;
        } else {
          request.body = json.encode(body);
        }
      }

      // Set timeout
      final timeoutDuration = timeout ?? SecurityConfig.defaultTimeout;
      
      final streamedResponse = await _client.send(request).timeout(
        timeoutDuration,
        onTimeout: () => throw TimeoutException(
          'Request timed out after ${timeoutDuration.inSeconds} seconds',
          timeoutDuration,
        ),
      );

      final response = await http.Response.fromStream(streamedResponse);
      
      // Log request in debug mode
      if (kDebugMode) {
        debugPrint('🔒 Secure $method request to: $url');
        debugPrint('🔒 Response status: ${response.statusCode}');
      }

      // Validate response
      _validateResponse(response);
      
      return response;
    } on SocketException catch (e) {
      throw NetworkException('Network error: ${e.message}');
    } on TimeoutException catch (e) {
      throw NetworkException('Request timeout: ${e.message}');
    } on HttpException catch (e) {
      throw NetworkException('HTTP error: ${e.message}');
    } catch (e) {
      throw SecurityException('Secure request failed: $e');
    }
  }

  /// Validate HTTP response for security
  void _validateResponse(http.Response response) {
    // Check for suspicious response codes
    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        throw AuthenticationException('Authentication failed');
      } else if (response.statusCode == 403) {
        throw AuthorizationException('Access forbidden');
      } else if (response.statusCode >= 500) {
        throw ServerException('Server error: ${response.statusCode}');
      } else {
        throw HttpException('HTTP error: ${response.statusCode}');
      }
    }

    // Validate content type for JSON responses
    final contentType = response.headers['content-type'];
    if (contentType != null && !contentType.contains('application/json') && 
        !contentType.contains('text/html') && !contentType.contains('text/plain')) {
      if (kDebugMode) {
        debugPrint('⚠️ Unexpected content type: $contentType');
      }
    }
  }

  /// Dispose of the HTTP client
  void dispose() {
    _client.close();
  }
}

/// Custom security exceptions
class SecurityException implements Exception {
  final String message;
  SecurityException(this.message);
  @override
  String toString() => 'SecurityException: $message';
}

class NetworkException implements Exception {
  final String message;
  NetworkException(this.message);
  @override
  String toString() => 'NetworkException: $message';
}

class AuthenticationException implements Exception {
  final String message;
  AuthenticationException(this.message);
  @override
  String toString() => 'AuthenticationException: $message';
}

class AuthorizationException implements Exception {
  final String message;
  AuthorizationException(this.message);
  @override
  String toString() => 'AuthorizationException: $message';
}

class ServerException implements Exception {
  final String message;
  ServerException(this.message);
  @override
  String toString() => 'ServerException: $message';
}
