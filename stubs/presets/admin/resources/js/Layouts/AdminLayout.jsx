import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ChartColumn,
    ChevronDown,
    Contact,
    FileText,
    Home,
    LayoutGrid,
    LogOut,
    Menu,
    Settings,
    ShieldUser,
    UserCircle2,
    Users,
    Wrench,
    BrainCircuit,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';

const navItems = [
    { route: 'dashboard', icon: Home, key: 'home' },
    { route: 'customers.index', icon: Contact, key: 'customers' },
    { route: 'orders.index', icon: LayoutGrid, key: 'orders' },
    { route: 'services.index', icon: Wrench, key: 'services' },
    { route: 'staff.index', icon: Users, key: 'staff' },
    { route: 'calendar.index', icon: CalendarDays, key: 'calendar' },
    { route: 'ai-settings.index', icon: BrainCircuit, key: 'aiSettings' },
    { route: 'settings.index', icon: ShieldUser, key: 'usersSettings' },
    { route: 'app-settings.index', icon: Settings, key: 'appSettings' },
];

export default function AdminLayout({ title, children }) {
    const { auth, locale = 'en', owlAdmin = {} } = usePage().props;
    const user = auth?.user;
    const brandName = owlAdmin?.brand_name ?? 'Service Admin';
    const logoPath = owlAdmin?.logo_path ?? '/images/company-logo.svg';
    const ai = owlAdmin?.ai ?? {};
    const aiConnected = !!ai.connected;
    const aiBadgeText = ai?.status_label
        ?? (aiConnected
            ? `AI: connected — ${ai.provider_label ?? ai.provider ?? 'Unknown'} / ${ai.model ?? 'unknown'}`
            : 'AI: not connected');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [statisticsOpen, setStatisticsOpen] = useState(route().current('statistics.*'));

    const uiText = {
        en: {
            home: 'Dashboard',
            customers: 'Customers',
            orders: 'Orders',
            services: 'Services',
            staff: 'Staff',
            calendar: 'Calendar',
            usersSettings: 'Users / Settings',
            appSettings: 'App settings',
            statistics: 'Statistics',
            logs: 'Logs',
            profile: 'Profile',
            logout: 'Logout',
            adminPanel: 'Admin Panel',
            aiSettings: 'AI Settings',
        },
        ru: {
            home: 'Дашборд',
            customers: 'Клиенты',
            orders: 'Заказы',
            services: 'Услуги',
            staff: 'Персонал',
            calendar: 'Календарь',
            usersSettings: 'Пользователи / Настройки',
            appSettings: 'Настройки приложения',
            statistics: 'Статистика',
            logs: 'Логи',
            profile: 'Профиль',
            logout: 'Выход',
            adminPanel: 'Панель администратора',
            aiSettings: 'AI Settings',
        },
        uk: {
            home: 'Дашборд',
            customers: 'Клієнти',
            orders: 'Замовлення',
            services: 'Послуги',
            staff: 'Персонал',
            calendar: 'Календар',
            usersSettings: 'Користувачі / Налаштування',
            appSettings: 'Налаштування застосунку',
            statistics: 'Статистика',
            logs: 'Логи',
            profile: 'Профіль',
            logout: 'Вихід',
            adminPanel: 'Панель адміністратора',
            aiSettings: 'AI Settings',
        },
    };
    const t = uiText[locale] ?? uiText.en;

    const navLinkClass = (active) =>
        `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
            active
                ? 'border-l-2 border-white bg-white/10 text-white'
                : 'text-slate-300 hover:bg-white/5 hover:text-white'
        }`;

    const renderNav = (mobile = false) => (
        <>
            {navItems.map(({ route: routeName, icon: Icon, key }) => (
                <Link
                    key={`${mobile ? 'mobile-' : ''}${routeName}`}
                    href={route(routeName)}
                    className={navLinkClass(route().current(`${routeName.split('.')[0]}*`) || route().current(routeName))}
                    onClick={() => mobile && setMobileMenuOpen(false)}
                >
                    <Icon className="h-4 w-4" />
                    {t[key]}
                </Link>
            ))}

            <div>
                <button
                    type="button"
                    onClick={() => setStatisticsOpen((open) => !open)}
                    className={`${navLinkClass(route().current('statistics.*'))} w-full`}
                >
                    <ChartColumn className="h-4 w-4" />
                    {t.statistics}
                    <ChevronDown className={`ml-auto h-4 w-4 transition ${statisticsOpen ? 'rotate-180' : ''}`} />
                </button>

                {statisticsOpen && (
                    <Link
                        href={route('statistics.logs')}
                        className={`${navLinkClass(route().current('statistics.logs'))} ml-5 mt-1`}
                        onClick={() => mobile && setMobileMenuOpen(false)}
                    >
                        <FileText className="h-4 w-4" />
                        {t.logs}
                    </Link>
                )}
            </div>
        </>
    );

    return (
        <div className="h-screen overflow-hidden bg-slate-50 text-slate-900">
            <div className="flex h-screen">
                <aside className="hidden w-72 flex-col bg-slate-900 text-white shadow-2xl lg:flex">
                    <div className="border-b border-white/10 px-6 py-5">
                        <div className="flex items-center gap-3">
                            <img src={logoPath} alt={brandName} className="h-10 w-10 rounded-xl bg-white/10 p-1" />
                            <div>
                                <p className="text-base font-semibold">{brandName}</p>
                                <p className="text-xs uppercase tracking-wide text-slate-400">{t.adminPanel}</p>
                            </div>
                        </div>
                    </div>

                    <nav className="flex h-full flex-1 flex-col px-4 pb-6 pt-4">
                        <div className="space-y-1.5">{renderNav()}</div>
                        <div className="mt-auto border-t border-white/10 pt-4">
                            <Link
                                href={route('profile.edit')}
                                className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                            >
                                <UserCircle2 className="h-4 w-4" />
                                {t.profile}
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                            >
                                <LogOut className="h-4 w-4" />
                                {t.logout}
                            </Link>
                        </div>
                    </nav>
                </aside>

                <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                    <SheetContent side="left" className="w-80 border-r-0 bg-slate-900 p-0 text-white">
                        <SheetHeader className="border-b border-white/10 px-6 py-5 text-left">
                            <SheetTitle className="text-base font-semibold text-white">{brandName}</SheetTitle>
                        </SheetHeader>
                        <nav className="flex h-full flex-1 flex-col px-4 pb-6 pt-4">
                            <div className="space-y-1.5">{renderNav(true)}</div>
                            <div className="mt-auto border-t border-white/10 pt-4">
                                <Link
                                    href={route('profile.edit')}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                                >
                                    <UserCircle2 className="h-4 w-4" />
                                    {t.profile}
                                </Link>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                                >
                                    <LogOut className="h-4 w-4" />
                                    {t.logout}
                                </Link>
                            </div>
                        </nav>
                    </SheetContent>
                </Sheet>

                <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
                    <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur-xl">
                        <div className="flex h-16 items-center justify-between px-4 sm:px-8">
                            <div className="flex items-center gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="h-9 w-9 lg:hidden"
                                    onClick={() => setMobileMenuOpen(true)}
                                >
                                    <Menu className="h-4 w-4" />
                                </Button>
                                <h1 className="text-base font-semibold text-slate-900">{title}</h1>
                            </div>
                            <div className="flex items-center gap-3">
                                <span
                                    className={`hidden max-w-[320px] truncate rounded-full px-3 py-1 text-xs font-semibold md:inline-flex ${
                                        aiConnected
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-red-100 text-red-700'
                                    }`}
                                    title={aiBadgeText}
                                >
                                    {aiBadgeText}
                                </span>
                                {user && (
                                    <div className="text-right">
                                        <p className="max-w-44 truncate text-sm font-medium text-slate-800">{user.name}</p>
                                        <p className="max-w-44 truncate text-xs text-slate-500">{user.email}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </header>

                    <main className="flex-1 overflow-y-auto p-4 sm:p-8">
                        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
