class VisaType {
  final int id;
  final int countryId;
  final String name;
  final String currency;
  final double price;
  final String processingTime;

  VisaType({
    required this.id,
    required this.countryId,
    required this.name,
    required this.currency,
    required this.price,
    required this.processingTime,
  });

  factory VisaType.fromJson(Map<String, dynamic> json) {
    return VisaType(
      id: int.parse(json['id'].toString()),
      countryId: int.parse(json['country_id'].toString()),
      name: json['name'] ?? '',
      currency: json['currency'] ?? 'INR',
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      processingTime: json['processing_time'] ?? 'Standard Processing',
    );
  }
}
