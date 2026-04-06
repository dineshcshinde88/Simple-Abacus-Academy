import { ReactNode } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import {
  LayoutDashboard,
  Users,
  BookOpen,
  FileText,
  Video,
  KeyRound,
  User,
  ShoppingBag,
  LogOut,
} from "lucide-react";

const menuItems = [
  { label: "Dashboard", icon: LayoutDashboard, to: "/student/dashboard" },
  { label: "Batches", icon: Users, to: "/student/batches" },
  { label: "Allocated Courses", icon: BookOpen, to: "/student/courses" },
  { label: "Worksheet Sub", icon: FileText, to: "/student/worksheets" },
  { label: "Video Tutorials", icon: Video, to: "/student/videos" },
  { label: "Change Password", icon: KeyRound, to: "/student/change-password" },
  { label: "Profile Details", icon: User, to: "/student/profile" },
  { label: "Orders", icon: ShoppingBag, to: "/student/orders" },
];

const TOKEN_KEY = "abacus_auth_token";

type StudentLayoutProps = {
  header: ReactNode;
  children: ReactNode;
};

const StudentLayout = ({ header, children }: StudentLayoutProps) => {
  const navigate = useNavigate();

  const handleLogout = () => {
    localStorage.removeItem(TOKEN_KEY);
    navigate("/student-login");
  };

  return (
    <div className="min-h-screen bg-slate-100 flex">
      <aside className="hidden lg:flex w-72 flex-col bg-[#5b21b6] text-white px-6 py-8 fixed inset-y-0">
        <div className="rounded-xl bg-white p-3 flex items-center justify-center">
          <img src="/abacus_logo.png" alt="Abacus Trainer" className="h-10 w-auto" />
        </div>
        <nav className="mt-8 flex-1 space-y-1">
          {menuItems.map((item) => (
            <NavLink
              key={item.label}
              to={item.to}
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
      </aside>

      <main className="flex-1 lg:ml-72">
        <header className="bg-white shadow-sm">
          <div className="px-6 py-6">{header}</div>
        </header>
        <section className="px-6 py-8">{children}</section>
      </main>
    </div>
  );
};

export default StudentLayout;
