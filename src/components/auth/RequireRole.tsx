import { ReactNode } from "react";
import { Navigate } from "react-router-dom";
import { useAuth } from "@/context/AuthContext";

type RequireRoleProps = {
  role: "student" | "tutor";
  children: ReactNode;
};

const RequireRole = ({ role, children }: RequireRoleProps) => {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center text-muted-foreground">Loading...</div>;
  }

  if (!user) {
    return <Navigate to="/" replace />;
  }

  if (user.role !== role) {
    return <Navigate to={user.role === "tutor" ? "/tutor/dashboard" : "/student/dashboard"} replace />;
  }

  return <>{children}</>;
};

export default RequireRole;
