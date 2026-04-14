import { Navigate } from "react-router-dom";
import { useTrainingAuth } from "@/context/TrainingAuthContext";

const TrainingProtectedRoute = ({
  children,
  role,
}: {
  children: React.ReactNode;
  role?: "admin" | "teacher";
}) => {
  const { user, loading } = useTrainingAuth();
  if (loading) return null;
  if (!user) return <Navigate to="/training/login" replace />;
  if (role && user.role !== role) return <Navigate to="/training/dashboard" replace />;
  return <>{children}</>;
};

export default TrainingProtectedRoute;
