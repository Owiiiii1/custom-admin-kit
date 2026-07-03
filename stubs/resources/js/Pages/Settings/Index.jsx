import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';

export default function SettingsIndex() {
    const { locale = 'en' } = usePage().props;

    const text = {
        en: {
            title: 'Settings',
            body: 'Core kit placeholder. User management and locale persistence require host auth routes and controllers.',
        },
        ru: {
            title: 'Настройки',
            body: 'Заглушка core kit. Управление пользователями требует auth-маршрутов и контроллеров хоста.',
        },
        uk: {
            title: 'Налаштування',
            body: 'Заглушка core kit. Керування користувачами потребує auth-маршрутів і контролерів хоста.',
        },
    };

    const t = text[locale] ?? text.en;

    return (
        <AdminLayout title={t.title}>
            <Head title="Settings" />
            <div className="app-widget p-4">
                <p className="text-sm text-slate-700">{t.body}</p>
            </div>
        </AdminLayout>
    );
}
