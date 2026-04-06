import Navbar from "@/components/layout/Navbar";
import Footer from "@/components/layout/Footer";
import { useAuth } from "@/context/AuthContext";
import { Users, Calendar, ClipboardCheck, BarChart3 } from "lucide-react";

const stats = [
  { label: "Total Students", value: "64", icon: Users },
  { label: "Today's Classes", value: "5", icon: Calendar },
  { label: "Pending Evaluations", value: "12", icon: ClipboardCheck },
  { label: "Avg Performance", value: "84%", icon: BarChart3 },
];

const TeacherDashboard = () => {
  const { user } = useAuth();

  return (
    <div className="min-h-screen">
      <Navbar />
      <main className="pt-16">
        <section className="gradient-hero py-16">
          <div className="container mx-auto px-4">
            <h1 className="text-3xl md:text-4xl font-heading font-bold text-primary-foreground">
              Teacher Dashboard
            </h1>
            <p className="text-primary-foreground/80 mt-2">
              Welcome {user?.name}. Manage students, classes, and performance.
            </p>
          </div>
        </section>

        <section className="py-12">
          <div className="container mx-auto px-4">
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {stats.map((stat) => (
                <div key={stat.label} className="bg-card border border-border rounded-xl p-5 shadow-card">
                  <div className="w-10 h-10 rounded-lg gradient-accent flex items-center justify-center mb-3">
                    <stat.icon className="w-5 h-5 text-accent-foreground" />
                  </div>
                  <p className="text-sm text-muted-foreground">{stat.label}</p>
                  <p className="text-xl font-heading font-bold text-foreground">{stat.value}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default TeacherDashboard;
