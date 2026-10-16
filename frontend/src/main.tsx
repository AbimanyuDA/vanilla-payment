import '@fontsource-variable/fraunces';
import '@fontsource-variable/public-sans';
import './styles/global.css';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, Navigate, RouterProvider } from 'react-router';

import { AdminLayout } from './components/layout/AdminLayout';
import { ToastProvider } from './components/ui/Toast';
import { ApiError } from './lib/api';
import { LoginPage } from './features/auth/LoginPage';
import { BuyerFormPage, BuyerListPage } from './features/buyers/BuyerPages';
import { DashboardPage } from './features/dashboard/DashboardPage';
import { InvoiceDetailPage } from './features/invoices/InvoiceDetailPage';
import { InvoiceFormPage } from './features/invoices/InvoiceFormPage';
import { InvoiceListPage } from './features/invoices/InvoiceListPage';
import { PaymentPage } from './features/payment/PaymentPage';
import { ProductFormPage, ProductListPage } from './features/products/ProductPages';
import { QuotationDetailPage } from './features/quotations/QuotationDetailPage';
import { QuotationFormPage } from './features/quotations/QuotationFormPage';
import { QuotationListPage } from './features/quotations/QuotationListPage';
import { ReportsPage } from './features/reports/ReportsPage';
import { CompanySettingsPage } from './features/settings/CompanySettingsPage';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: (count, err) => !(err instanceof ApiError && err.status < 500) && count < 2,
    },
  },
});

const router = createBrowserRouter([
  { path: '/', element: <Navigate to="/admin/dashboard" replace /> },
  { path: '/pay/:token', element: <PaymentPage /> },
  { path: '/admin/login', element: <LoginPage /> },
  {
    path: '/admin',
    element: <AdminLayout />,
    children: [
      { index: true, element: <Navigate to="dashboard" replace /> },
      { path: 'dashboard', element: <DashboardPage /> },
      { path: 'invoices', element: <InvoiceListPage /> },
      { path: 'invoices/new', element: <InvoiceFormPage /> },
      { path: 'invoices/:id', element: <InvoiceDetailPage /> },
      { path: 'invoices/:id/edit', element: <InvoiceFormPage /> },
      { path: 'reports', element: <ReportsPage /> },
      { path: 'quotations', element: <QuotationListPage /> },
      { path: 'quotations/new', element: <QuotationFormPage /> },
      { path: 'quotations/:id', element: <QuotationDetailPage /> },
      { path: 'quotations/:id/edit', element: <QuotationFormPage /> },
      { path: 'buyers', element: <BuyerListPage /> },
      { path: 'buyers/new', element: <BuyerFormPage /> },
      { path: 'buyers/:id/edit', element: <BuyerFormPage /> },
      { path: 'products', element: <ProductListPage /> },
      { path: 'products/new', element: <ProductFormPage /> },
      { path: 'products/:id/edit', element: <ProductFormPage /> },
      { path: 'settings/company', element: <CompanySettingsPage /> },
    ],
  },
  { path: '*', element: <Navigate to="/admin/dashboard" replace /> },
]);

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <ToastProvider>
        <RouterProvider router={router} />
      </ToastProvider>
    </QueryClientProvider>
  </StrictMode>,
);
