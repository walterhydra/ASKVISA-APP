import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/dio_client.dart';
import '../domain/visa_type.dart';

final visaTypesProvider = FutureProvider.family<List<VisaType>, String>((ref, countryId) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get(
    'visa-types',
    queryParameters: {'country_id': countryId},
  );
  
  if (response.data['success'] == true) {
    final List data = response.data['data'];
    return data.map((json) => VisaType.fromJson(json)).toList();
  } else {
    throw Exception(response.data['message'] ?? 'Failed to load visa types');
  }
});
