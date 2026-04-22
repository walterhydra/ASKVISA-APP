import 'package:go_router/go_router.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../features/home/presentation/home_screen.dart';
import '../features/visa/presentation/visa_selection_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(
        path: '/',
        name: 'home',
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: '/visa/:countryId',
        name: 'visaSelection',
        builder: (context, state) {
          final countryId = state.pathParameters['countryId']!;
          final countryName = state.uri.queryParameters['name'] ?? 'Country';
          return VisaSelectionScreen(countryId: countryId, countryName: countryName);
        },
      ),
    ],
  );
});
