import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/dio_client.dart';
import '../domain/country.dart';

final countriesProvider = FutureProvider<List<Country>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('countries');
  
  if (response.data['success'] == true) {
    final List data = response.data['data'];
    return data.map((json) => Country.fromJson(json)).toList();
  } else {
    throw Exception(response.data['message'] ?? 'Failed to load countries');
  }
});
