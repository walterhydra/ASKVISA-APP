class Country {
  final int id;
  final String name;

  Country({required this.id, required this.name});

  factory Country.fromJson(Map<String, dynamic> json) {
    return Country(
      id: int.parse(json['id'].toString()),
      name: json['country_name'] ?? '',
    );
  }
}
