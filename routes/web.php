<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BiController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CrmController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DistributionController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventLeadController;
use App\Http\Controllers\Admin\EventQuoteController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\GuestRequestController as AdminGuestRequestController;
use App\Http\Controllers\Admin\HousekeepingController;
use App\Http\Controllers\Admin\HrController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\KdsController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\MeetingRoomController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PoolController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\QrLocationController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RoomBoardController;
use App\Http\Controllers\Admin\RoomCategoryController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\SatisfactionController as AdminSatisfactionController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SplashScreenController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventEnquiryController;
use App\Http\Controllers\Guest\AppController as GuestAppController;
use App\Http\Controllers\Guest\AuthController as GuestAuthController;
use App\Http\Controllers\Guest\RequestController as GuestRequestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentCheckoutController;
use App\Http\Controllers\PilotageController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\QrMenuController;
use App\Http\Controllers\QrOrderController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Site vitrine — Hôtel Résidence Khadija
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/l-hotel', [PageController::class, 'about'])->name('about');
Route::get('/restaurant', [PageController::class, 'restaurant'])->name('restaurant');
Route::get('/espaces', [PageController::class, 'spaces'])->name('spaces');
Route::get('/seminaires-evenements', [PageController::class, 'events'])->name('events');
Route::post('/seminaires-evenements/devis', [EventEnquiryController::class, 'store'])->name('events.enquiry')->middleware('throttle:6,1');
Route::get('/experiences', [PageController::class, 'experiences'])->name('experiences');
Route::get('/galerie', [PageController::class, 'gallery'])->name('gallery');

Route::get('/chambres', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/chambres/{slug}', [RoomController::class, 'show'])->name('rooms.show');

Route::get('/offres', [OfferController::class, 'index'])->name('offers.index');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send')->middleware('throttle:6,1');

// Enquête de satisfaction (accès par jeton, sans compte).
Route::get('/avis/{token}', [SatisfactionController::class, 'show'])->name('satisfaction.show');
Route::post('/avis/{token}', [SatisfactionController::class, 'store'])->name('satisfaction.store')->middleware('throttle:10,1');
Route::get('/avis/{token}/merci', [SatisfactionController::class, 'done'])->name('satisfaction.done');

/*
|--------------------------------------------------------------------------
| Tunnel de réservation (front)
|--------------------------------------------------------------------------
*/
Route::prefix('reservation')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'start'])->name('start');
    Route::post('/', [BookingController::class, 'storeSearch'])->name('search');

    Route::get('/chambres', [BookingController::class, 'rooms'])->name('rooms');
    Route::post('/chambres', [BookingController::class, 'storeRoom'])->name('rooms.store');
    Route::post('/devis', [BookingController::class, 'quote'])->middleware('throttle:60,1')->name('quote');

    Route::get('/options', [BookingController::class, 'extras'])->name('extras');
    Route::post('/options', [BookingController::class, 'storeExtras'])->name('extras.store');

    Route::get('/recapitulatif', [BookingController::class, 'summary'])->name('summary');
    Route::post('/recapitulatif', [BookingController::class, 'confirm'])
        ->name('confirm')->middleware('throttle:10,1');

    Route::get('/confirmation', [BookingController::class, 'done'])->name('done');
    Route::post('/{reference}/paiement', [BookingController::class, 'payOnline'])
        ->middleware('throttle:10,1')->name('pay');
});

/*
|--------------------------------------------------------------------------
| Paiement en ligne (§25) — page hébergée + webhook prestataire
|--------------------------------------------------------------------------
*/
Route::get('/paiement/retour/{provider}/{intent}', [PaymentCheckoutController::class, 'return'])->name('pay.return');
Route::post('/paiement/webhook/{provider}', [PaymentCheckoutController::class, 'webhook'])
    ->middleware('throttle:120,1')->name('pay.webhook');
Route::get('/paiement/{intent}', [PaymentCheckoutController::class, 'show'])->name('pay.checkout');
Route::post('/paiement/{intent}', [PaymentCheckoutController::class, 'process'])
    ->middleware('throttle:15,1')->name('pay.process');
Route::get('/paiement/{intent}/recu', [PaymentCheckoutController::class, 'receipt'])->name('pay.receipt');

/*
|--------------------------------------------------------------------------
| QR Ordering — carte digitale, commande à table & room service
|--------------------------------------------------------------------------
*/
Route::get('/carte', [QrMenuController::class, 'publicIndex'])->name('menu.public');
Route::get('/carte/{code}', [QrMenuController::class, 'show'])->name('qr.show');
Route::post('/carte/{code}/commande', [QrOrderController::class, 'store'])
    ->middleware('throttle:20,1')->name('qr.order');
Route::get('/carte/{code}/suivi/{reference}', [QrOrderController::class, 'track'])->name('qr.track');
Route::post('/carte/{code}/addition', [QrOrderController::class, 'bill'])
    ->middleware('throttle:10,1')->name('qr.bill');
Route::post('/carte/{code}/appel', [ServiceRequestController::class, 'store'])
    ->middleware('throttle:12,1')->name('qr.call');
Route::post('/carte/{code}/paiement/{reference}', [QrOrderController::class, 'payOnline'])
    ->middleware('throttle:10,1')->name('qr.pay');

/*
|--------------------------------------------------------------------------
| Pointage — borne self-service (§44)
|--------------------------------------------------------------------------
*/
Route::get('/pointage/scan/{employee:matricule}', [ClockController::class, 'scan'])
    ->middleware(['signed:relative', 'throttle:60,1'])->name('clock.scan');
Route::get('/pointage/photo/{employee:matricule}', [ClockController::class, 'photo'])->name('clock.photo');
Route::get('/pointage/{matricule?}', [ClockController::class, 'show'])->name('clock.show');
Route::post('/pointage', [ClockController::class, 'clock'])->middleware('throttle:20,1')->name('clock.store');

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Application mobile invité — PWA `/app` (§58–60)
|--------------------------------------------------------------------------
*/
Route::prefix('app')->name('guest.')->group(function () {
    Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('manifest');
    Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('sw');
    Route::get('/hors-ligne', [PwaController::class, 'offline'])->name('offline');

    Route::get('/entrer', [GuestAuthController::class, 'showLogin'])->name('login');
    Route::post('/entrer', [GuestAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::get('/lien/{reference}', [GuestAuthController::class, 'magic'])->middleware('signed:relative')->name('magic');
    Route::post('/quitter', [GuestAuthController::class, 'logout'])->name('logout');

    Route::middleware('guest.app')->group(function () {
        Route::get('/', [GuestAppController::class, 'home'])->name('home');
        Route::get('/sejour', [GuestAppController::class, 'stayPage'])->name('stay');
        Route::post('/sejour/payer', [GuestAppController::class, 'payBalance'])->middleware('throttle:10,1')->name('pay');
        Route::get('/carte', [GuestAppController::class, 'menu'])->name('menu');
        Route::get('/wifi', [GuestAppController::class, 'wifi'])->name('wifi');
        Route::get('/fidelite', [GuestAppController::class, 'loyalty'])->name('loyalty');
        Route::post('/fidelite/inscription', [GuestAppController::class, 'enrol'])->name('loyalty.enrol');

        Route::get('/demandes', [GuestRequestController::class, 'index'])->name('requests');
        Route::post('/demandes', [GuestRequestController::class, 'store'])->middleware('throttle:20,1')->name('requests.store');
        Route::post('/demandes/service', [GuestRequestController::class, 'requestService'])->middleware('throttle:20,1')->name('requests.service');
        Route::post('/demandes/{guestRequest}/annuler', [GuestRequestController::class, 'cancel'])->name('requests.cancel');
    });
});

/*
|--------------------------------------------------------------------------
| Distribution — flux iCal public + webhook réservations de canal (§29–31)
|--------------------------------------------------------------------------
*/
Route::get('/calendrier/{category}.ics', [ChannelController::class, 'ical'])->name('channel.ical');
Route::post('/distribution/webhook/{channel}', [ChannelController::class, 'webhook'])
    ->middleware('throttle:120,1')->name('channel.webhook');

/*
|--------------------------------------------------------------------------
| PMS / Back-office
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'show'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')->name('admin.login.attempt');

    Route::get('/admin/mot-de-passe-oublie', [AuthController::class, 'showForgot'])->name('admin.password.request');
    Route::post('/admin/mot-de-passe-oublie', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('admin.password.email');
    Route::get('/admin/reinitialiser/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/admin/reinitialiser', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('admin.password.update');
});
Route::post('/admin/logout', [AuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications in-app (tout le personnel connecté)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/tout-lu', [NotificationController::class, 'readAll'])->name('notifications.read_all');
    Route::post('/notifications/{notification}/lu', [NotificationController::class, 'read'])->name('notifications.read');

    // Réservations & Front Office
    Route::middleware('role:reception,direction')->group(function () {
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/nouvelle', [AdminReservationController::class, 'create'])->name('reservations.create');
        Route::get('/reservations/{reservation}', [AdminReservationController::class, 'show'])->name('reservations.show');
        Route::get('/reservations/{reservation}/facture', [AdminReservationController::class, 'invoice'])->name('reservations.invoice');
        Route::get('/reservations/{reservation}/app-qr', [AdminReservationController::class, 'appQr'])->name('reservations.app_qr');
    });

    Route::middleware('role:reception')->group(function () {
        Route::post('/reservations', [AdminReservationController::class, 'store'])->name('reservations.store');
        Route::post('/reservations/{reservation}/confirmer', [AdminReservationController::class, 'confirm'])->name('reservations.confirm');
        Route::post('/reservations/{reservation}/annuler', [AdminReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::post('/reservations/{reservation}/no-show', [AdminReservationController::class, 'noShow'])->name('reservations.no_show');
        Route::post('/reservations/{reservation}/check-in', [AdminReservationController::class, 'checkIn'])->name('reservations.check_in');
        Route::post('/reservations/{reservation}/check-out', [AdminReservationController::class, 'checkOut'])->name('reservations.check_out');
        Route::post('/reservations/{reservation}/paiement', [AdminReservationController::class, 'storePayment'])->name('reservations.payment');
    });

    // Chambres (housekeeping + réception)
    Route::middleware('role:reception,housekeeping,direction')->group(function () {
        Route::get('/chambres', [RoomBoardController::class, 'index'])->name('rooms.index');
    });

    // Gestion du parc de chambres (création / édition / activation) — direction & réception
    Route::middleware('role:direction,reception')->group(function () {
        Route::get('/chambres/gestion', [AdminRoomController::class, 'index'])->name('rooms.manage');
        Route::post('/chambres/gestion', [AdminRoomController::class, 'store'])->name('rooms.store');
        Route::put('/chambres/gestion/{room}', [AdminRoomController::class, 'update'])->name('rooms.update');
        Route::post('/chambres/gestion/{room}/bascule', [AdminRoomController::class, 'toggle'])->name('rooms.toggle');
        Route::delete('/chambres/gestion/{room}', [AdminRoomController::class, 'destroy'])->name('rooms.destroy');
    });

    // Messages du formulaire de contact (vitrine) — réception & direction
    Route::middleware('role:direction,reception')->group(function () {
        Route::get('/messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{message}/traiter', [ContactMessageController::class, 'handle'])->name('messages.handle');
    });

    // Demandes clients (app invité) — réception + services concernés
    Route::middleware('role:reception,housekeeping,maintenance,direction')->group(function () {
        Route::get('/demandes-clients', [AdminGuestRequestController::class, 'index'])->name('guest_requests.index');
        Route::post('/demandes-clients/{guestRequest}/vue', [AdminGuestRequestController::class, 'acknowledge'])->name('guest_requests.ack');
        Route::post('/demandes-clients/{guestRequest}/traitee', [AdminGuestRequestController::class, 'resolve'])->name('guest_requests.resolve');
    });
    Route::post('/chambres/{room}/statut', [RoomBoardController::class, 'updateStatus'])
        ->middleware('role:reception,housekeeping')->name('rooms.status');

    // Tableau de bord intelligent d'une chambre (GEMS) — déclaré après
    // « /chambres/gestion » pour ne pas capturer ce segment littéral.
    Route::get('/chambres/{room}', [RoomBoardController::class, 'show'])
        ->middleware('role:reception,housekeeping,direction,finance')->name('rooms.show');

    // CRM 360° · Fidélité · Marketing (§18–20, §52–57)
    Route::middleware('role:reception,direction,marketing')->group(function () {
        Route::get('/clients', [GuestController::class, 'index'])->name('guests.index');
        Route::get('/clients/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::put('/clients/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::post('/clients/{guest}/consentement', [GuestController::class, 'consent'])->name('guests.consent');
        Route::post('/clients/{guest}/fidelite/inscrire', [GuestController::class, 'enroll'])->name('guests.enroll');
        Route::post('/clients/{guest}/fidelite/ajuster', [GuestController::class, 'adjustPoints'])->name('guests.points.adjust');
        Route::post('/clients/{guest}/fidelite/utiliser', [GuestController::class, 'redeemPoints'])->name('guests.points.redeem');
        Route::post('/clients/{guest}/interaction', [GuestController::class, 'storeInteraction'])->name('guests.interaction');

        Route::get('/crm', [CrmController::class, 'dashboard'])->name('crm.dashboard');
        Route::get('/crm/fidelite', [CrmController::class, 'loyalty'])->name('crm.loyalty');
        Route::get('/crm/segments', [CrmController::class, 'segments'])->name('crm.segments');
        Route::post('/crm/segments', [CrmController::class, 'storeSegment'])->name('crm.segments.store');
        Route::post('/crm/segments/apercu', [CrmController::class, 'previewSegment'])->name('crm.segments.preview');
        Route::delete('/crm/segments/{segment}', [CrmController::class, 'destroySegment'])->name('crm.segments.destroy');
        Route::get('/crm/codes-promo', [CrmController::class, 'promos'])->name('crm.promos');
        Route::post('/crm/codes-promo', [CrmController::class, 'storePromo'])->name('crm.promos.store');
        Route::post('/crm/codes-promo/{promo}/bascule', [CrmController::class, 'togglePromo'])->name('crm.promos.toggle');

        // Satisfaction & e-réputation (§ audit #10)
        Route::get('/satisfaction', [AdminSatisfactionController::class, 'index'])->name('satisfaction.index');
        Route::post('/satisfaction/invitations', [AdminSatisfactionController::class, 'invite'])->name('satisfaction.invite');
        Route::get('/satisfaction/{survey:id}', [AdminSatisfactionController::class, 'show'])->name('satisfaction.show');
        Route::put('/satisfaction/{survey:id}', [AdminSatisfactionController::class, 'update'])->name('satisfaction.update');
        Route::post('/satisfaction/{survey:id}/publication', [AdminSatisfactionController::class, 'togglePublish'])->name('satisfaction.publish');
    });

    // Commercial & Événements (MICE) — §21–24
    Route::middleware('role:commercial,direction')->group(function () {
        // Pipeline commercial
        Route::get('/commercial', [EventLeadController::class, 'index'])->name('events.pipeline');
        Route::get('/commercial/affaires/nouveau', [EventLeadController::class, 'create'])->name('events.leads.create');
        Route::post('/commercial/affaires', [EventLeadController::class, 'store'])->name('events.leads.store');
        Route::get('/commercial/affaires/{lead}', [EventLeadController::class, 'show'])->name('events.leads.show');
        Route::put('/commercial/affaires/{lead}', [EventLeadController::class, 'update'])->name('events.leads.update');
        Route::post('/commercial/affaires/{lead}/etape', [EventLeadController::class, 'advance'])->name('events.leads.advance');
        Route::post('/commercial/affaires/{lead}/activite', [EventLeadController::class, 'storeActivity'])->name('events.leads.activity');
        Route::post('/commercial/affaires/{lead}/activite/{activity}/faite', [EventLeadController::class, 'completeActivity'])->name('events.leads.activity.done');

        // Devis
        Route::get('/commercial/devis/nouveau', [EventQuoteController::class, 'create'])->name('events.quotes.create');
        Route::post('/commercial/devis', [EventQuoteController::class, 'store'])->name('events.quotes.store');
        Route::get('/commercial/devis/{quote}', [EventQuoteController::class, 'show'])->name('events.quotes.show');
        Route::get('/commercial/devis/{quote}/modifier', [EventQuoteController::class, 'edit'])->name('events.quotes.edit');
        Route::put('/commercial/devis/{quote}', [EventQuoteController::class, 'update'])->name('events.quotes.update');
        Route::post('/commercial/devis/{quote}/envoyer', [EventQuoteController::class, 'send'])->name('events.quotes.send');
        Route::post('/commercial/devis/{quote}/accepter', [EventQuoteController::class, 'accept'])->name('events.quotes.accept');
        Route::post('/commercial/devis/{quote}/refuser', [EventQuoteController::class, 'decline'])->name('events.quotes.decline');
        Route::get('/commercial/devis/{quote}/imprimer', [EventQuoteController::class, 'print'])->name('events.quotes.print');
        Route::delete('/commercial/devis/{quote}', [EventQuoteController::class, 'destroy'])->name('events.quotes.destroy');

        // Événements
        Route::get('/evenements', [EventController::class, 'index'])->name('events.index');
        Route::post('/evenements', [EventController::class, 'store'])->name('events.store');
        Route::get('/evenements/salles', [EventController::class, 'spaces'])->name('events.spaces');
        Route::post('/evenements/salles', [EventController::class, 'storeSpace'])->name('events.spaces.store');
        Route::put('/evenements/salles/{space}', [EventController::class, 'updateSpace'])->name('events.spaces.update');
        Route::get('/evenements/{event}', [EventController::class, 'show'])->name('events.show');
        Route::get('/evenements/{event}/contrat', [EventController::class, 'contract'])->name('events.contract');
        Route::post('/evenements/{event}/salle', [EventController::class, 'addSpace'])->name('events.space.add');
        Route::delete('/evenements/{event}/salle/{booking}', [EventController::class, 'removeSpace'])->name('events.space.remove');
        Route::post('/evenements/{event}/confirmer', [EventController::class, 'confirm'])->name('events.confirm');
        Route::post('/evenements/{event}/annuler', [EventController::class, 'cancel'])->name('events.cancel');
        Route::post('/evenements/{event}/realiser', [EventController::class, 'complete'])->name('events.complete');
        Route::post('/evenements/{event}/agenda', [EventController::class, 'storeAgendaItem'])->name('events.agenda.store');
        Route::delete('/evenements/{event}/agenda/{item}', [EventController::class, 'deleteAgendaItem'])->name('events.agenda.destroy');
        Route::post('/evenements/{event}/acompte', [EventController::class, 'payDeposit'])->name('events.deposit');
        Route::post('/evenements/{event}/solde', [EventController::class, 'paySettlement'])->name('events.settlement');
    });

    // Campagnes marketing — direction & marketing uniquement.
    Route::middleware('role:direction,marketing')->group(function () {
        Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing.index');
        Route::post('/marketing', [MarketingController::class, 'store'])->name('marketing.store');
        Route::get('/marketing/{campaign}', [MarketingController::class, 'show'])->name('marketing.show');
        Route::post('/marketing/{campaign}/recalculer', [MarketingController::class, 'rebuild'])->name('marketing.rebuild');
        Route::post('/marketing/{campaign}/envoyer', [MarketingController::class, 'send'])->name('marketing.send');
        Route::post('/marketing/{campaign}/annuler', [MarketingController::class, 'cancel'])->name('marketing.cancel');
    });

    // Restaurant — KDS, commandes, service (§21, §27–30)
    Route::middleware('role:restaurant,reception')->group(function () {
        Route::get('/cuisine', [KdsController::class, 'index'])->name('kds.index');
        Route::post('/cuisine/{order}/avancer', [KdsController::class, 'advance'])->name('kds.advance');

        // Caisse restaurant (POS)
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');                       // tableau de bord caissier
        Route::get('/pos/vente', [PosController::class, 'register'])->name('pos.register');           // écran de vente tactile
        Route::post('/pos/vente/encaisser', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::post('/pos/vente/attente', [PosController::class, 'hold'])->name('pos.hold');
        Route::post('/pos/vente/{order}/annuler', [PosController::class, 'void'])->name('pos.void');
        Route::get('/pos/vente/{order}/ticket', [PosController::class, 'receipt'])->name('pos.receipt');
        Route::get('/pos/vente/{order}/facture', [PosController::class, 'invoice'])->name('pos.invoice');
        Route::post('/pos/vente/{order}/facture/envoyer', [PosController::class, 'sendInvoice'])->name('pos.invoice.send');
        Route::post('/pos/autoriser', [PosController::class, 'authorize'])->name('pos.authorize');
        Route::post('/pos/{order}/rembourser', [PosController::class, 'refund'])->name('pos.refund');
        Route::post('/pos/caisse/ouvrir', [PosController::class, 'openSession'])->name('pos.session.open');
        Route::post('/pos/caisse/{cashSession}/cloturer', [PosController::class, 'closeSession'])->name('pos.session.close');
        Route::get('/pos/caisse/rapport', [PosController::class, 'sessionReport'])->name('pos.session.report');
        // P3 — poste (identification légère) + reporting
        Route::post('/pos/poste', [PosController::class, 'storeStation'])->name('pos.station');
        Route::post('/pos/quitter-poste', [PosController::class, 'exitStation'])->name('pos.station.exit');
        Route::get('/pos/rapports', [PosController::class, 'reports'])->name('pos.reports');
        // P2 — salle, client hôtel, room service, petit-déjeuner
        Route::get('/pos/salle', [PosController::class, 'floor'])->name('pos.floor');
        Route::get('/pos/clients', [PosController::class, 'guests'])->name('pos.guests');
        Route::get('/pos/room-service', [PosController::class, 'roomService'])->name('pos.room_service');
        Route::post('/pos/room-service/{order}/avancer', [KdsController::class, 'advance'])->name('pos.room_service.advance');
        Route::get('/pos/petit-dejeuner', [PosController::class, 'breakfast'])->name('pos.breakfast');
        Route::post('/pos/petit-dejeuner', [PosController::class, 'breakfastStore'])->name('pos.breakfast.store');

        Route::get('/commandes', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{order}/encaisser', [AdminOrderController::class, 'pay'])->name('orders.pay');
        Route::post('/commandes/{order}/chambre', [AdminOrderController::class, 'chargeToRoom'])->name('orders.charge');
        Route::post('/commandes/{order}/annuler', [AdminOrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('/service', [AdminServiceRequestController::class, 'index'])->name('service.index');
        Route::post('/service/{serviceRequest}/vu', [AdminServiceRequestController::class, 'acknowledge'])->name('service.ack');
        Route::post('/service/{serviceRequest}/traite', [AdminServiceRequestController::class, 'resolve'])->name('service.resolve');
    });

    // PIN caisse (autorisations POS) — les managers le définissent eux-mêmes.
    Route::middleware('role:direction')->group(function () {
        Route::get('/pos/pin', [PosController::class, 'editPin'])->name('pos.pin.edit');
        Route::put('/pos/pin', [PosController::class, 'updatePin'])->name('pos.pin.update');
    });

    // Carte & QR — administration (§21, §23)
    Route::middleware('role:restaurant,direction')->group(function () {
        Route::get('/carte', [MenuController::class, 'index'])->name('menu.index');
        Route::post('/carte/categorie', [MenuController::class, 'storeCategory'])->name('menu.category.store');
        Route::put('/carte/categorie/{category}', [MenuController::class, 'updateCategory'])->name('menu.category.update');
        Route::delete('/carte/categorie/{category}', [MenuController::class, 'destroyCategory'])->name('menu.category.destroy');
        Route::get('/carte/article/nouveau', [MenuController::class, 'createItem'])->name('menu.item.create');
        Route::post('/carte/article', [MenuController::class, 'storeItem'])->name('menu.item.store');
        Route::get('/carte/article/{item}', [MenuController::class, 'editItem'])->name('menu.item.edit');
        Route::put('/carte/article/{item}', [MenuController::class, 'updateItem'])->name('menu.item.update');
        Route::post('/carte/article/{item}/dispo', [MenuController::class, 'toggleItem'])->name('menu.item.toggle');
        Route::delete('/carte/article/{item}', [MenuController::class, 'destroyItem'])->name('menu.item.destroy');

        Route::get('/qr', [QrLocationController::class, 'index'])->name('qr.index');
        Route::get('/qr/impression', [QrLocationController::class, 'print'])->name('qr.print');
        Route::get('/qr/{qrLocation}/svg', [QrLocationController::class, 'svg'])->name('qr.svg');
        Route::post('/qr', [QrLocationController::class, 'store'])->name('qr.store');
        Route::put('/qr/{qrLocation}', [QrLocationController::class, 'update'])->name('qr.update');
    });

    // Housekeeping (§33–34)
    Route::middleware('role:housekeeping,reception,direction')->group(function () {
        Route::get('/menage', [HousekeepingController::class, 'index'])->name('housekeeping.index');
        Route::get('/menage/mobile', [HousekeepingController::class, 'mobile'])->name('housekeeping.mobile');
        Route::get('/menage/incidents', [HousekeepingController::class, 'incidents'])->name('housekeeping.incidents');
    });
    Route::middleware('role:housekeeping,reception')->group(function () {
        Route::post('/menage/incidents', [HousekeepingController::class, 'storeIncident'])->name('housekeeping.incidents.store');
        Route::post('/menage/incidents/{incident}/resoudre', [HousekeepingController::class, 'resolveIncident'])->name('housekeeping.incidents.resolve');
    });
    Route::middleware('role:housekeeping')->group(function () {
        Route::post('/menage/generer', [HousekeepingController::class, 'generate'])->name('housekeeping.generate');
        Route::post('/menage/{task}/affecter', [HousekeepingController::class, 'assign'])->name('housekeeping.assign');
        Route::post('/menage/{task}/statut', [HousekeepingController::class, 'updateStatus'])->name('housekeeping.status');
        Route::get('/menage/{task}/controle', [HousekeepingController::class, 'inspect'])->name('housekeeping.inspect');
        Route::post('/menage/{task}/controle', [HousekeepingController::class, 'storeInspection'])->name('housekeeping.inspect.store');
    });

    // Piscine — réservation de transats / cabanas
    Route::middleware('role:reception,direction')->group(function () {
        Route::get('/piscine', [PoolController::class, 'index'])->name('pool.index');
        Route::post('/piscine/reservations', [PoolController::class, 'store'])->name('pool.store');
        Route::post('/piscine/reservations/{poolReservation}/statut', [PoolController::class, 'updateStatus'])->name('pool.status');
        Route::get('/piscine/parc', [PoolController::class, 'assets'])->name('pool.assets');
        Route::post('/piscine/parc', [PoolController::class, 'storeAsset'])->name('pool.assets.store');
        Route::post('/piscine/parc/{poolAsset}/bascule', [PoolController::class, 'toggleAsset'])->name('pool.assets.toggle');
    });

    // Salles & séminaires — planning d'occupation (le parc & les affectations vivent dans Événements)
    Route::middleware('role:commercial,direction,reception')->group(function () {
        Route::get('/salles', [MeetingRoomController::class, 'index'])->name('salles.index');
    });
    Route::middleware('role:commercial,direction')->group(function () {
        Route::post('/salles/devis', [MeetingRoomController::class, 'storeQuoteRequest'])->name('salles.quote_request');
    });

    // Maintenance (§35–36)
    Route::middleware('role:maintenance,reception,direction')->group(function () {
        Route::get('/maintenance', [MaintenanceController::class, 'tickets'])->name('maintenance.tickets');
        Route::get('/maintenance/tickets/nouveau', [MaintenanceController::class, 'createTicket'])->name('maintenance.ticket.create');
        Route::post('/maintenance/tickets', [MaintenanceController::class, 'storeTicket'])->name('maintenance.ticket.store');
        Route::get('/maintenance/tickets/{ticket}', [MaintenanceController::class, 'showTicket'])->name('maintenance.ticket');
    });
    Route::middleware('role:maintenance,direction')->group(function () {
        Route::put('/maintenance/tickets/{ticket}', [MaintenanceController::class, 'updateTicket'])->name('maintenance.ticket.update');
        Route::get('/maintenance/equipements', [MaintenanceController::class, 'equipment'])->name('maintenance.equipment');
        Route::post('/maintenance/equipements', [MaintenanceController::class, 'storeEquipment'])->name('maintenance.equipment.store');
        Route::put('/maintenance/equipements/{equipment}', [MaintenanceController::class, 'updateEquipment'])->name('maintenance.equipment.update');
        Route::get('/maintenance/plans', [MaintenanceController::class, 'plans'])->name('maintenance.plans');
        Route::post('/maintenance/plans', [MaintenanceController::class, 'storePlan'])->name('maintenance.plans.store');
        Route::put('/maintenance/plans/{plan}', [MaintenanceController::class, 'updatePlan'])->name('maintenance.plans.update');
        Route::delete('/maintenance/plans/{plan}', [MaintenanceController::class, 'destroyPlan'])->name('maintenance.plans.destroy');
        Route::post('/maintenance/plans/executer', [MaintenanceController::class, 'runPlans'])->name('maintenance.plans.run');
    });

    // Stocks & Achats (§37–39)
    Route::middleware('role:stock,direction,reception')->group(function () {
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/mouvements', [StockController::class, 'movements'])->name('stock.movements');
        Route::get('/stock/transferts', [StockController::class, 'transfers'])->name('stock.transfers');
        Route::get('/stock/inventaires', [InventoryController::class, 'index'])->name('stock.inventory.index');
        Route::get('/stock/inventaires/{inventoryCount}', [InventoryController::class, 'show'])->name('stock.inventory.show');
        Route::get('/achats', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/achats/nouveau', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::get('/achats/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    });
    Route::middleware('role:stock')->group(function () {
        Route::get('/stock/articles/nouveau', [StockController::class, 'createItem'])->name('stock.item.create');
        Route::post('/stock/articles', [StockController::class, 'storeItem'])->name('stock.item.store');
        Route::get('/stock/articles/{item}', [StockController::class, 'editItem'])->name('stock.item.edit');
        Route::put('/stock/articles/{item}', [StockController::class, 'updateItem'])->name('stock.item.update');
        Route::post('/stock/mouvements', [StockController::class, 'storeMovement'])->name('stock.movement.store');
        Route::post('/stock/transferts', [StockController::class, 'storeTransfer'])->name('stock.transfer.store');
        Route::post('/stock/inventaires', [InventoryController::class, 'store'])->name('stock.inventory.store');
        Route::put('/stock/inventaires/{inventoryCount}', [InventoryController::class, 'update'])->name('stock.inventory.update');
        Route::post('/stock/inventaires/{inventoryCount}/cloturer', [InventoryController::class, 'close'])->name('stock.inventory.close');
        Route::get('/stock/fournisseurs', [StockController::class, 'suppliers'])->name('stock.suppliers');
        Route::post('/stock/fournisseurs', [StockController::class, 'storeSupplier'])->name('stock.suppliers.store');
        Route::put('/stock/fournisseurs/{supplier}', [StockController::class, 'updateSupplier'])->name('stock.suppliers.update');
        Route::post('/stock/magasins', [StockController::class, 'storeWarehouse'])->name('stock.warehouses.store');
        Route::post('/achats', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::post('/achats/{purchase}/etape/{to}', [PurchaseController::class, 'transition'])->name('purchases.transition');
        Route::post('/achats/{purchase}/reception', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::post('/achats/{purchase}/facture', [PurchaseController::class, 'storeInvoice'])->name('purchases.invoice.store');
    });
    // Validation des achats : direction.
    Route::post('/achats/{purchase}/valider', [PurchaseController::class, 'transition'])
        ->defaults('to', 'approve')->middleware('role:direction')->name('purchases.approve');
    // Règlement fournisseur : finance.
    Route::post('/achats/factures/{invoice}/regler', [PurchaseController::class, 'payInvoice'])
        ->middleware('role:finance')->name('purchases.invoice.pay');

    // Distribution & Channel Manager (§29–31)
    Route::middleware('role:direction,reception')->group(function () {
        Route::get('/distribution', [DistributionController::class, 'index'])->name('distribution.index');
        Route::put('/distribution/canaux/{channel}', [DistributionController::class, 'updateChannel'])->name('distribution.channels.update');
        Route::post('/distribution/canaux/{channel}/pousser', [DistributionController::class, 'push'])->name('distribution.channels.push');
        Route::post('/distribution/canaux/{channel}/tester', [DistributionController::class, 'testConnection'])->name('distribution.channels.test');
        Route::post('/distribution/canaux/{channel}/importer', [DistributionController::class, 'pull'])->name('distribution.channels.pull');
        Route::get('/distribution/calendrier', [DistributionController::class, 'calendar'])->name('distribution.calendar');
        Route::post('/distribution/calendrier', [DistributionController::class, 'updateCalendar'])->name('distribution.calendar.update');
        Route::get('/distribution/tarifs', [DistributionController::class, 'rates'])->name('distribution.rates');
        Route::put('/distribution/tarifs/{channel}', [DistributionController::class, 'updateRates'])->name('distribution.rates.update');
        Route::get('/distribution/reservations', [DistributionController::class, 'reservations'])->name('distribution.reservations');
        Route::post('/distribution/reservations/simuler', [DistributionController::class, 'simulateReservation'])->name('distribution.reservations.simulate');
        Route::get('/distribution/journal', [DistributionController::class, 'log'])->name('distribution.log');
    });

    // Décisionnel — BI, KPI & rapports (§26–28)
    Route::middleware('role:direction,finance')->group(function () {
        Route::get('/bi', [BiController::class, 'dashboard'])->name('bi.dashboard');
        Route::get('/bi/rapports', [BiController::class, 'reports'])->name('bi.reports');
        Route::get('/bi/rapports/{key}', [BiController::class, 'report'])->name('bi.report');
        Route::get('/bi/rapports/{key}/export', [BiController::class, 'export'])->name('bi.export');
        Route::get('/bi/assistant', [BiController::class, 'assistant'])->name('bi.assistant');
        Route::post('/bi/assistant', [BiController::class, 'ask'])->name('bi.assistant.ask');
        Route::get('/bi/planifications', [BiController::class, 'schedules'])->name('bi.schedules');
        Route::post('/bi/planifications', [BiController::class, 'storeSchedule'])->name('bi.schedules.store');
        Route::post('/bi/planifications/{schedule}/bascule', [BiController::class, 'toggleSchedule'])->name('bi.schedules.toggle');
        Route::delete('/bi/planifications/{schedule}', [BiController::class, 'destroySchedule'])->name('bi.schedules.destroy');
    });

    // Paiement en ligne — suivi des intentions (§25)
    Route::middleware('role:finance,direction,reception')->group(function () {
        Route::get('/paiements', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/paiements/{intent}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/paiements/{intent}/encaisser', [PaymentController::class, 'markPaid'])->name('payments.mark');
        Route::post('/paiements/{intent}/rembourser', [PaymentController::class, 'refund'])->name('payments.refund');
        Route::post('/paiements/{intent}/annuler', [PaymentController::class, 'cancel'])->name('payments.cancel');
        Route::post('/reservations/{reservation}/lien-paiement', [PaymentController::class, 'linkForReservation'])->name('payments.link.reservation');
    });
    Route::post('/evenements/{event}/lien-paiement', [PaymentController::class, 'linkForEvent'])
        ->middleware('role:commercial,direction')->name('payments.link.event');

    // Finance & Caisses (§40, §42)
    Route::middleware('role:finance,direction')->group(function () {
        Route::get('/finance', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
        Route::get('/finance/journal', [FinanceController::class, 'journal'])->name('finance.journal');
        Route::get('/finance/creances', [FinanceController::class, 'receivables'])->name('finance.receivables');
    });
    Route::middleware('role:finance,reception')->group(function () {
        Route::get('/finance/caisses', [FinanceController::class, 'cashSessions'])->name('finance.cash');
        Route::post('/finance/caisses/ouvrir', [FinanceController::class, 'openSession'])->name('finance.cash.open');
        Route::post('/finance/caisses/{cashSession}/cloturer', [FinanceController::class, 'closeSession'])->name('finance.cash.close');
    });
    Route::post('/finance/journal', [FinanceController::class, 'storeTransaction'])
        ->middleware('role:finance')->name('finance.transaction.store');

    // Comptabilité (§41)
    Route::middleware('role:finance,direction')->group(function () {
        Route::get('/comptabilite', [AccountingController::class, 'index'])->name('accounting.index');
        Route::get('/comptabilite/ecritures', [AccountingController::class, 'entries'])->name('accounting.entries');
        Route::get('/comptabilite/balance', [AccountingController::class, 'balance'])->name('accounting.balance');
        Route::get('/comptabilite/grand-livre', [AccountingController::class, 'generalLedger'])->name('accounting.general_ledger');
        Route::get('/comptabilite/grand-livre/{account}', [AccountingController::class, 'ledger'])->name('accounting.ledger');
        Route::get('/comptabilite/resultat', [AccountingController::class, 'incomeStatement'])->name('accounting.income_statement');
        Route::get('/comptabilite/bilan', [AccountingController::class, 'balanceSheet'])->name('accounting.balance_sheet');
        Route::get('/comptabilite/tva', [AccountingController::class, 'vat'])->name('accounting.vat');
        Route::get('/comptabilite/exercices', [AccountingController::class, 'fiscalYears'])->name('accounting.fiscal_years');
        Route::get('/comptabilite/export', [AccountingController::class, 'export'])->name('accounting.export');
    });
    Route::middleware('role:finance')->group(function () {
        Route::post('/comptabilite/ecritures', [AccountingController::class, 'storeEntry'])->name('accounting.entries.store');
        Route::post('/comptabilite/ecritures/{entry}/contre-passer', [AccountingController::class, 'reverseEntry'])->name('accounting.entries.reverse');
    });
    Route::middleware('role:direction')->group(function () {
        Route::post('/comptabilite/exercices/{fiscalYear}/cloturer', [AccountingController::class, 'closeFiscalYear'])->name('accounting.fiscal_years.close');
        Route::post('/comptabilite/exercices/{fiscalYear}/rouvrir', [AccountingController::class, 'reopenFiscalYear'])->name('accounting.fiscal_years.reopen');
    });

    // Ressources humaines (§43–46)
    Route::middleware('role:rh,direction')->group(function () {
        Route::get('/rh', [HrController::class, 'dashboard'])->name('hr.dashboard');
        Route::get('/rh/employes', [EmployeeController::class, 'index'])->name('hr.employees.index');
        Route::get('/rh/employes/nouveau', [EmployeeController::class, 'create'])->name('hr.employees.create');
        Route::get('/rh/badges', [EmployeeController::class, 'badges'])->name('hr.badges');
        Route::get('/rh/employes/{employee}', [EmployeeController::class, 'show'])->name('hr.employees.show');
        Route::get('/rh/employes/{employee}/qr', [EmployeeController::class, 'qrSvg'])->name('hr.employees.qr');
        Route::get('/rh/employes/{employee}/carte', [EmployeeController::class, 'card'])->name('hr.employees.card');
        Route::get('/rh/employes/{employee}/attestation/{type}', [EmployeeController::class, 'attestation'])->name('hr.employees.attestation');
        Route::get('/rh/documents/{document}', [EmployeeController::class, 'downloadDocument'])->name('hr.documents.download');
        Route::get('/rh/conges', [LeaveController::class, 'index'])->name('hr.leave.index');
        Route::get('/rh/services', [HrController::class, 'departments'])->name('hr.departments');
        Route::get('/rh/planning', [ScheduleController::class, 'index'])->name('hr.schedule');
        Route::get('/rh/pointage', [AttendanceController::class, 'index'])->name('hr.attendance');
        Route::get('/rh/formations', [HrController::class, 'trainings'])->name('hr.trainings');
        Route::get('/rh/paie', [PayrollController::class, 'index'])->name('hr.payroll.index');
        Route::get('/rh/paie/{run}', [PayrollController::class, 'show'])->name('hr.payroll.show');
        Route::get('/rh/paie/{run}/bulletin/{payslip}', [PayrollController::class, 'payslip'])->name('hr.payroll.payslip');
    });

    // Actions RH (rôle rh, sauf approbations = direction)
    Route::middleware('role:rh')->group(function () {
        Route::post('/rh/employes', [EmployeeController::class, 'store'])->name('hr.employees.store');
        Route::put('/rh/employes/{employee}', [EmployeeController::class, 'update'])->name('hr.employees.update');
        Route::post('/rh/employes/{employee}/contrat', [EmployeeController::class, 'storeContract'])->name('hr.employees.contract');
        Route::post('/rh/employes/{employee}/document', [EmployeeController::class, 'uploadDocument'])->name('hr.employees.document');
        Route::post('/rh/employes/{employee}/pin', [EmployeeController::class, 'setPin'])->name('hr.employees.pin');
        Route::post('/rh/employes/{employee}/photo', [EmployeeController::class, 'uploadPhoto'])->name('hr.employees.photo');
        Route::post('/rh/employes/{employee}/remuneration', [EmployeeController::class, 'saveComponents'])->name('hr.employees.components');
        Route::post('/rh/employes/{employee}/avance', [EmployeeController::class, 'storeAdvance'])->name('hr.employees.advance');
        Route::post('/rh/employes/{employee}/evaluation', [HrController::class, 'storeEvaluation'])->name('hr.employees.evaluation');

        Route::post('/rh/services', [HrController::class, 'storeDepartment'])->name('hr.departments.store');
        Route::post('/rh/fonctions', [HrController::class, 'storePosition'])->name('hr.positions.store');
        Route::post('/rh/feries', [HrController::class, 'storeHoliday'])->name('hr.holidays.store');
        Route::delete('/rh/feries/{holiday}', [HrController::class, 'destroyHoliday'])->name('hr.holidays.destroy');

        Route::post('/rh/conges', [LeaveController::class, 'store'])->name('hr.leave.store');
        Route::post('/rh/conges/{leave}/refuser', [LeaveController::class, 'reject'])->name('hr.leave.reject');
        Route::post('/rh/conges/{leave}/annuler', [LeaveController::class, 'cancel'])->name('hr.leave.cancel');

        Route::post('/rh/planning', [ScheduleController::class, 'store'])->name('hr.schedule.store');
        Route::delete('/rh/planning/{shift}', [ScheduleController::class, 'destroy'])->name('hr.schedule.destroy');
        Route::post('/rh/planning/{shift}/remplacer', [ScheduleController::class, 'replace'])->name('hr.schedule.replace');
        Route::post('/rh/planning/dupliquer', [ScheduleController::class, 'duplicateWeek'])->name('hr.schedule.duplicate');

        Route::post('/rh/pointage/{employee}/pointer', [AttendanceController::class, 'clock'])->name('hr.attendance.clock');
        Route::post('/rh/pointage/{employee}/absent', [AttendanceController::class, 'markAbsent'])->name('hr.attendance.absent');
        Route::post('/rh/pointage/log/{log}/corriger', [AttendanceController::class, 'correct'])->name('hr.attendance.correct');

        Route::post('/rh/formations', [HrController::class, 'storeTraining'])->name('hr.trainings.store');
        Route::put('/rh/formations/{training}', [HrController::class, 'updateTraining'])->name('hr.trainings.update');

        Route::post('/rh/paie', [PayrollController::class, 'store'])->name('hr.payroll.store');
        Route::post('/rh/paie/{run}/generer', [PayrollController::class, 'generate'])->name('hr.payroll.generate');
        Route::post('/rh/paie/{run}/element', [PayrollController::class, 'storeAdjustment'])->name('hr.payroll.adjustment');
        Route::delete('/rh/paie/{run}/element/{adjustment}', [PayrollController::class, 'destroyAdjustment'])->name('hr.payroll.adjustment.destroy');
        Route::post('/rh/paie/{run}/payer', [PayrollController::class, 'markPaid'])->name('hr.payroll.pay');
    });

    // Approbations RH réservées à la direction.
    Route::middleware('role:direction')->group(function () {
        Route::post('/rh/conges/{leave}/approuver', [LeaveController::class, 'approve'])->name('hr.leave.approve');
        Route::post('/rh/paie/{run}/approuver', [PayrollController::class, 'approve'])->name('hr.payroll.approve');
        Route::post('/rh/employes/{employee}/sortie', [EmployeeController::class, 'terminate'])->name('hr.employees.terminate');
    });

    // Paramètres & audit
    Route::middleware('role:direction')->group(function () {
        Route::get('/parametres', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/parametres/categorie/{category}', [SettingsController::class, 'updateCategory'])->name('settings.category');
        Route::put('/parametres/tarif/{ratePlan}', [SettingsController::class, 'updateRatePlan'])->name('settings.rate_plan');

        // Réglages du site (identité, contact, réservation, fidélité, SEO, Wi-Fi…)
        Route::get('/parametres/site', [SiteSettingController::class, 'edit'])->name('site_settings.edit');
        Route::put('/parametres/site', [SiteSettingController::class, 'update'])->name('site_settings.update');

        // Écran d'accueil (splash screen) de la vitrine
        Route::get('/parametres/ecran-accueil', [SplashScreenController::class, 'edit'])->name('splash.edit');
        Route::put('/parametres/ecran-accueil', [SplashScreenController::class, 'update'])->name('splash.update');
        Route::get('/parametres/ecran-accueil/apercu', [SplashScreenController::class, 'preview'])->name('splash.preview');

        // Identité visuelle (logo couleur + monochrome) — repris sur toute l'application
        Route::get('/parametres/identite-visuelle', [BrandingController::class, 'edit'])->name('branding.edit');
        Route::put('/parametres/identite-visuelle', [BrandingController::class, 'update'])->name('branding.update');

        // CRUD complet des catégories de chambre (contenu vitrine + tarifs)
        Route::get('/parametres/categories', [RoomCategoryController::class, 'index'])->name('room_categories.index');
        Route::get('/parametres/categories/nouvelle', [RoomCategoryController::class, 'create'])->name('room_categories.create');
        Route::post('/parametres/categories', [RoomCategoryController::class, 'store'])->name('room_categories.store');
        Route::get('/parametres/categories/{category}', [RoomCategoryController::class, 'edit'])->name('room_categories.edit');
        Route::put('/parametres/categories/{category}', [RoomCategoryController::class, 'update'])->name('room_categories.update');
        Route::delete('/parametres/categories/{category}', [RoomCategoryController::class, 'destroy'])->name('room_categories.destroy');
    });
    Route::get('/audit', [AuditController::class, 'index'])->middleware('role:admin')->name('audit.index');
});

/*
|--------------------------------------------------------------------------
| Pilotage — application directeur (PWA installable, sans push)
|--------------------------------------------------------------------------
*/
Route::prefix('pilotage')->name('pilotage.')->middleware(['auth', 'role:direction'])->group(function () {
    Route::get('/', [PilotageController::class, 'home'])->name('home');
    Route::get('/manifest.webmanifest', [PilotageController::class, 'manifest'])->name('manifest');
    Route::get('/sw.js', [PilotageController::class, 'serviceWorker'])->name('sw');
});
