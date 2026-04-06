import { ReactNode } from "react";
import { Navigate } from "react-router-dom";

const TOKEN_KEY = "abacus_auth_token";

type ProtectedRouteProps = {
  children: ReactNode;
  role?: "student" | "tutor";
};

const ProtectedRoute = ({ children, role }: ProtectedRouteProps) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) {
    return <Navigate to="/student-login" replace />;
  }

  if (role) {
    try {
      const payload = JSON.parse(atob(token.split(".")[1] || ""));
      if (payload?.role !== role) {
        return <Navigate to={payload?.role === "tutor" ? "/tutor/dashboard" : "/student/dashboard"} replace />;
      }
    } catch {
      return <Navigate to="/student-login" replace />;
    }
  }

  return <>{children}</>;
};

export default ProtectedRoute;
