export function getIpActionColor(action: string): string {
  switch (action) {
    case "create":
      return "bg-green-100 text-green-700";
    case "update":
      return "bg-blue-100 text-blue-700";
    case "delete":
      return "bg-red-100 text-red-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}

export function getAuthActionColor(action: string): string {
  switch (action) {
    case "login":
      return "bg-green-100 text-green-700";
    case "logout":
      return "bg-gray-100 text-gray-700";
    case "register":
      return "bg-blue-100 text-blue-700";
    case "token_refresh":
      return "bg-yellow-100 text-yellow-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}
