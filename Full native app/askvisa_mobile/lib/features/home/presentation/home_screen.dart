import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../data/country_repository.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final countriesAsync = ref.watch(countriesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Where are you going?', style: TextStyle(fontWeight: FontWeight.bold)),
        centerTitle: false,
        actions: const [
          ZoomLogo(),
        ],
      ),
      body: countriesAsync.when(
        data: (countries) => RefreshIndicator(
          onRefresh: () => ref.refresh(countriesProvider.future),
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
            itemCount: countries.length,
            itemBuilder: (context, index) {
              final country = countries[index];
              return Card(
                elevation: 2,
                margin: const EdgeInsets.only(bottom: 12.0),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  leading: const CircleAvatar(
                    backgroundColor: Colors.blueAccent,
                    child: Icon(Icons.flight_takeoff, color: Colors.white),
                  ),
                  title: Text(country.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w500)),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    context.pushNamed(
                      'visaSelection',
                      pathParameters: {'countryId': country.id.toString()},
                      queryParameters: {'name': country.name},
                    );
                  },
                ),
              );
            },
          ),
        ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, stack) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 64, color: Colors.red),
              const SizedBox(height: 16),
              Text('Error loading countries\n$err', textAlign: TextAlign.center),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.refresh(countriesProvider),
                child: const Text('Retry'),
              )
            ],
          ),
        ),
      ),
    );
  }
}

class ZoomLogo extends StatefulWidget {
  const ZoomLogo({super.key});

  @override
  State<ZoomLogo> createState() => _ZoomLogoState();
}

class _ZoomLogoState extends State<ZoomLogo> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 1200),
      vsync: this,
    )..repeat(reverse: true);
    
    _animation = Tween<double>(begin: 0.85, end: 1.15).animate(CurvedAnimation(
      parent: _controller,
      curve: Curves.easeInOut,
    ));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return ScaleTransition(
      scale: _animation,
      child: const Padding(
        padding: EdgeInsets.symmetric(horizontal: 16.0),
        child: Icon(
          Icons.public, 
          color: Colors.blueAccent, 
          size: 28
        ),
      ),
    );
  }
}
