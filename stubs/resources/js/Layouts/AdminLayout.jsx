import { Link, usePage } from '@inertiajs/react';
import {
    ChartColumn,
    ChevronDown,
    FileText,
    Home,
    LogOut,
    Menu,
    Settings,
    ShieldUser,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/Components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';

const coreNavItems = [
    { route: 'dashboard', icon: Home, labelKey: 'home' },
];

export default function AdminLayout({ title, children }) {
    const { auth, locale = 'en', owlAdmin = {} } = usePage().props;
    const user = auth?.user;
    const brandName = owlAdmin.brand_name ?? 'Service Admin';
    const logoPath = owlAdmin.logo_path ?? '/images/company-logo.svg';

    const [statisticsOpen, setStatisticsOpen] = useState(route().current('statistics.*'));
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const uiText = {
        en: {
            home: 'Home',
            appSettings: 'App settings',
            settings: 'Settings',
            logout: 'Logout',
            statistics: 'Statistics',
            logs: 'Logs',
            adminPanel: 'Admin Panel',
            profile: 'Profile',
        },
        ru: {
            home: 'Главная',
            appSettings: 'Настройки приложения',
            settings: 'Настройки',
            logout: 'Выход',
            statistics: 'Статистика',
            logs: 'Логи',
            adminPanel: 'Панель администратора',
            profile: 'Профиль',
        },
        uk: {
            home: 'Головна',
            appSettings: 'Налаштування застосунку',
            settings: 'Налаштування',
            logout: 'Вихід',
            statistics: 'Статистика',
            logs: 'Логи',
            adminPanel: 'Панель адміністратора',
            profile: 'Профіль',
        },
    };
    const t = uiText[locale] ?? uiText.en;

    const navLinkClass = (active) =>
        `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition ${
            active
                ? 'bg-white/15 text-white'
                : 'text-indigo-100 hover:bg-white/10 hover:text-white'
        }`;

    return (
        <div className="min-h-screen bg-slate-100">
            <div className="flex min-h-screen">
                <aside className="hidden w-64 flex-col bg-indigo-950 text-white lg:flex">
                    <div className="flex items-center gap-3 border-b border-white/10 px-5 py-5">
                        <img src={logoPath} alt="" className="h-9 w-9 rounded-lg bg-white/10 p-1" />
                        <div>
                            <p className="text-base font-semibold text-white">{brandName}</p>
                            <p className="text-xs text-indigo-200">{t.adminPanel}</p>
                        </div>
                    </div>

                    <nav className="flex-1 space-y-1 p-4">
                        {coreNavItems.map(({ route: routeName, icon: Icon, labelKey }) => (
                            <Link
                                key={routeName}
                                href={route(routeName)}
                                className={navLinkClass(route().current(routeName))}
                            >
                                <Icon className="h-4 w-4" />
                                {t[labelKey]}
                            </Link>
                        ))}

                        <Link
                            href={route('app-settings.index')}
                            className={navLinkClass(route().current('app-settings.*'))}
                        >
                            <Settings className="h-4 w-4" />
                            {t.appSettings}
                        </Link>

                        <div>
                            <button
                                type="button"
                                onClick={() => setStatisticsOpen((open) => !open)}
                                className={`${navLinkClass(route().current('statistics.*'))} w-full`}
                            >
                                <ChartColumn className="h-4 w-4" />
                                {t.statistics}
                                <ChevronDown
                                    className={`ml-auto h-4 w-4 transition ${statisticsOpen ? 'rotate-180' : ''}`}
                                />
                            </button>
                            {statisticsOpen && (
                                <Link
                                    href={route('statistics.logs')}
                                    className={`${navLinkClass(route().current('statistics.logs'))} ml-6 mt-1`}
                                >
                                    <FileText className="h-4 w-4" />
                                    {t.logs}
                                </Link>
                            )}
                        </div>

                        <Link
                            href={route('settings.index')}
                            className={navLinkClass(route().current('settings.*'))}
                        >
                            <ShieldUser className="h-4 w-4" />
                            {t.settings}
                        </Link>
                    </nav>

                    {user && (
                        <div className="border-t border-white/10 p-4">
                            <p className="truncate text-sm font-medium">{user.name}</p>
                            <p className="truncate text-xs text-indigo-200">{user.email}</p>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="mt-3 inline-flex items-center gap-2 text-xs text-indigo-200 hover:text-white"
                            >
                                <LogOut className="h-3.5 w-3.5" />
                                {t.logout}
                            </Link>
                        </div>
                    )}
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="flex items-center justify-between border-b border-indigo-200 bg-indigo-100/70 px-4 py-3 lg:px-6">
                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="lg:hidden"
                                onClick={() => setMobileMenuOpen(true)}
                            >
                                <Menu className="h-4 w-4" />
                            </Button>
                            <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
                        </div>
                        <span className="text-sm font-medium text-slate-600">{brandName}</span>
                    </header>

                    <main className="flex-1 p-4 lg:p-6">{children}</main>
                </div>
            </div>

            <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                <SheetContent side="left" className="w-72 bg-indigo-950 text-white">
                    <SheetHeader>
                        <SheetTitle className="text-white">{brandName}</SheetTitle>
                    </SheetHeader>
                    <nav className="mt-6 space-y-1">
                        {coreNavItems.map(({ route: routeName, icon: Icon, labelKey }) => (
                            <Link
                                key={routeName}
                                href={route(routeName)}
                                className={navLinkClass(route().current(routeName))}
                                onClick={() => setMobileMenuOpen(false)}
                            >
                                <Icon className="h-4 w-4" />
                                {t[labelKey]}
                            </Link>
                        ))}
                    </nav>
                </SheetContent>
            </Sheet>
        </div>
    );
}
