import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../data/visa_repository.dart';

class VisaSelectionScreen extends ConsumerWidget {
  final String countryId;
  final String countryName;

  const VisaSelectionScreen({
    super.key,
    required this.countryId,
    required this.countryName,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final visasAsync = ref.watch(visaTypesProvider(countryId));

    return Scaffold(
      appBar: AppBar(
        title: Text('Visa for $countryName'),
      ),
      body: visasAsync.when(
        data: (visas) {
          if (visas.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.flight_off, size: 64, color: Colors.grey),
                    const SizedBox(height: 16),
                    Text(
                      'No online visa processing currently available for $countryName.',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 18, color: Colors.grey),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () => context.pop(),
                      child: const Text('Go Back'),
                    )
                  ],
                ),
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16.0),
            itemCount: visas.length,
            itemBuilder: (context, index) {
              final visaType = visas[index];
              return Card(
                elevation: 3,
                margin: const EdgeInsets.only(bottom: 16.0),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              visaType.name,
                              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: Colors.blue.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              '${visaType.currency} ${visaType.price.toStringAsFixed(2)}',
                              style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          const Icon(Icons.timer_outlined, size: 16, color: Colors.grey),
                          const SizedBox(width: 8),
                          Text(
                            'Processing: ${visaType.processingTime}',
                            style: const TextStyle(color: Colors.grey),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          onPressed: () {
                            // Navigate to dynamic application form
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Dynamic form coming soon!')),
                            );
                          },
                          child: const Text('Apply Now'),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, stack) => Center(child: Text('Error: $err')),
      ),
    );
  }
}
