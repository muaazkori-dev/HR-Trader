import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'constants/rider_constants.dart';
import 'providers/rider_auth_provider.dart';
import 'providers/rider_orders_provider.dart';
import 'screens/rider_login_screen.dart';
import 'screens/rider_main_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
    ),
  );
  runApp(const HRTradersRiderApp());
}

class HRTradersRiderApp extends StatelessWidget {
  const HRTradersRiderApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => RiderAuthProvider()),
        ChangeNotifierProvider(create: (_) => RiderOrdersProvider()),
      ],
      child: Consumer<RiderAuthProvider>(
        builder: (context, auth, _) {
          return MaterialApp(
            title: RiderConstants.appName,
            debugShowCheckedModeBanner: false,
            theme: ThemeData(
              useMaterial3: true,
              colorScheme: ColorScheme.fromSeed(
                seedColor: RiderConstants.primary,
                primary: RiderConstants.primary,
                secondary: RiderConstants.accent,
              ),
              scaffoldBackgroundColor: RiderConstants.scaffoldBg,
              fontFamily: 'Roboto',
              appBarTheme: const AppBarTheme(
                elevation: 0,
                centerTitle: false,
                iconTheme: IconThemeData(color: Colors.white),
                titleTextStyle: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            home: auth.isAuthenticated ? const RiderMainScreen() : const RiderLoginScreen(),
          );
        },
      ),
    );
  }
}
