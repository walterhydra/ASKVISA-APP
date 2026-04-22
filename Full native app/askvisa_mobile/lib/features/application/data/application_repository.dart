import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/dio_client.dart';
import '../domain/question.dart';

final questionsProvider = FutureProvider.family<List<ApplicationQuestion>, String>((ref, countryId) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get(
    'questions',
    queryParameters: {'country_id': countryId},
  );
  
  if (response.data['success'] == true) {
    final List data = response.data['data'];
    return data.map((json) => ApplicationQuestion.fromJson(json)).toList();
  } else {
    throw Exception(response.data['message'] ?? 'Failed to load questions');
  }
});
