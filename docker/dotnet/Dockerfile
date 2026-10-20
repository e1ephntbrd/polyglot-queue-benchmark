FROM mcr.microsoft.com/dotnet/sdk:8.0

WORKDIR /app

COPY src-dotnet/Worker.csproj ./
RUN dotnet restore

COPY src-dotnet/ ./

CMD ["tail", "-f", "/dev/null"]
