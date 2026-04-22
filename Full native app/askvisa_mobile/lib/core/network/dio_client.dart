import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(
    BaseOptions(
      baseUrl: 'http://10.0.2.2:8000/api/', // For Android Emulator pointing to local machine
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {
        'Accept': 'application/json',
      },
    ),
  );
  
  // You can add interceptors here for token injection, logging, etc.
  dio.interceptors.add(LogInterceptor(responseBody: true));
  
  return dio;
});
