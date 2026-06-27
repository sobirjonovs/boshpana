FROM python:3.11-slim

ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Default to simulation so the image runs with zero Telegram credentials.
# Override in compose/CLI for REAL mode: `python main.py`
CMD ["python", "main.py", "--simulation"]
