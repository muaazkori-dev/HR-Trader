import 'package:flutter_test/flutter_test.dart';
import 'package:hr_traders_customer/main.dart';

void main() {
  testWidgets('HR Traders App smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const HRTradersApp());
    expect(find.byType(HRTradersApp), findsOneWidget);
  });
}
