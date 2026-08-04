import { ReactNode, useState } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import {
  LayoutDashboard,
  BookOpen,
  FileText,
  KeyRound,
  User,
  ShoppingBag,
  ReceiptText,
  Award,
  LogOut,
  Menu,
  X,
} from "lucide-react";

const menuItems = [
  { label: "Dashboard", icon: LayoutDashboard, to: "/student/dashboard" },
  { label: "Allocated Courses", icon: BookOpen, to: "/student/courses" },
  { label: "Worksheet Subscription", icon: FileText, to: "/student/worksheets" },
  { label: "Change Password", icon: KeyRound, to: "/student/change-password" },
  { label: "Profile Details", icon: User, to: "/student/profile" },
  { label: "Shop", icon: ShoppingBag, to: "/student/shop" },
  { label: "Payment History", icon: ReceiptText, to: "/student/orders" },
  { label: "Certificates", icon: Award, to: "/student/certificates" },
];

const TOKEN_KEY = "abacus_auth_token";

type StudentLayoutProps = {
  header: ReactNode;
  children: ReactNode;
};

const StudentLayout = ({ header, children }: StudentLayoutProps) => {
  const navigate = useNavigate();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const handleLogout = () => {
    localStorage.removeItem(TOKEN_KEY);
    navigate("/student-login");
  };

  const sidebar = (
    <>
      <div className="rounded-xl bg-white p-3 flex items-center justify-center">
        <img src="/abacus_logo.png" alt="Simple Abacus" className="h-10 w-auto" />
      </div>
      <nav className="mt-8 flex-1 space-y-1">
        {menuItems.map((item) => (
          <NavLink
            key={item.label}
            to={item.to}
            onClick={() => setSidebarOpen(false)}
            className={({ isActive }) =>
              `flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition ${
                isActive ? "bg-orange-500 text-white" : "hover:bg-white/10"
              }`
            }
          >
            <item.icon className="h-4 w-4" />
            {item.label}
          </NavLink>
        ))}
      </nav>
      <button
        type="button"
        onClick={handleLogout}
        className="mt-6 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold bg-white/10 hover:bg-white/20 transition"
      >
        <LogOut className="h-4 w-4" />
        Logout
      </button>
    </>
  );

  return (
    <div className="min-h-screen bg-slate-100 flex">
      {sidebarOpen && (
        <button
          type="button"
          aria-label="Close sidebar"
          className="fixed inset-0 z-40 bg-black/40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      <aside className="hidden lg:flex w-72 flex-col bg-[#5b21b6] text-white px-6 py-8 fixed inset-y-0">
        {sidebar}
      </aside>

      <aside
        className={`fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-[#5b21b6] px-6 py-8 text-white shadow-2xl transition-transform duration-300 lg:hidden ${
          sidebarOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <button
          type="button"
          aria-label="Close sidebar"
          className="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20"
          onClick={() => setSidebarOpen(false)}
        >
          <X className="h-5 w-5" />
        </button>
        {sidebar}
      </aside>

      <main className="flex-1 lg:ml-72">
        <header className="bg-white shadow-sm">
          <div className="px-4 py-4 sm:px-6 sm:py-6">
            <div className="flex items-start gap-3">
              <button
                type="button"
                aria-label="Open sidebar"
                className="mt-1 rounded-xl border border-slate-200 bg-white p-2 text-[#5b21b6] shadow-sm lg:hidden"
                onClick={() => setSidebarOpen(true)}
              >
                <Menu className="h-5 w-5" />
              </button>
              <div className="min-w-0 flex-1">{header}</div>
            </div>
          </div>
        </header>
        <section className="px-4 py-6 sm:px-6 sm:py-8">{children}</section>
      </main>
    </div>
  );
};

export default StudentLayout;
