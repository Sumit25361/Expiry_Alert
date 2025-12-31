# Credit Card Fraud Detection using Big Data and Spark Streaming

## Project Description
This project addresses the critical issue of credit card fraud by leveraging Big Data technologies to detect fraudulent transactions in real-time. Traditional fraud detection systems often struggle with the volume and velocity of modern transaction data. This system utilizes **Apache Spark Streaming** for real-time processing, **Kafka** for data ingestion, and **HBase/MongoDB** for storage to identify suspicious activities instantly based on predefined rules and geolocation logic.

## Objectives of the Project
- **Real-time Detection:** Process and analyze transaction data as it arrives to detect fraud immediately.
- **Scalability:** Build a system capable of handling high-throughput transaction streams.
- **Accuracy:** Implement robust validation rules to minimize false positives and false negatives.
- **Geospatial Analysis:** Use location data to identify physically impossible transactions (e.g., card usage in two distant locations within a short time).
- **Data Storage:** Efficiently store transaction history for auditing and further analysis.

## System Architecture
The system follows a standard Big Data streaming architecture:
1. **Data Source:** Transactions are simulated or ingested from a source (e.g., CSV, API).
2. **Data Ingestion (Kafka):** Transactions are pushed to a Kafka topic.
3. **Processing Engine (Spark Streaming):** Spark consumes data from Kafka, applies validation rules, and performs geospatial analysis.
4. **Rule Engine:** A dedicated module checks transactions against specific fraud criteria.
5. **Storage (HBase/MongoDB):** Valid and fraudulent transactions are stored for record-keeping.
6. **Visualization:** Statistics and alerts can be visualized on a dashboard.

## Project Structure
The repository is organized as follows:

- **`data/uszipsv.csv`**: Contains US zip code data including latitude and longitude, used for geospatial distance calculations.
- **`db/dao.py`**: Data Access Object layer handling connections and operations with the database (HBase or MongoDB).
- **`db/geo_map.py`**: Helper module for performing geospatial calculations (e.g., Haversine formula) to determine distances between transaction locations.
- **`rules/rules.py`**: Defines the core logic and business rules for identifying fraud (e.g., checking score limits, velocity, and location).
- **`driver.py`**: The main entry point of the application. It initializes the Spark context, sets up the streaming job, and orchestrates the flow.
- **`LogicFinal.pdf`**: A document detailing the logic, algorithms, and mathematical models used in the project.

## Technologies Used
- **Programming Language:** Python (PySpark)
- **Big Data Frameworks:** Apache Spark, Spark Streaming
- **Message Broker:** Apache Kafka
- **Databases:** HBase (NoSQL) or MongoDB
- **Libraries:** `pykafka`, `happybase`, `geopy` (or custom geo logic)
- **Environment:** Linux / Windows (with WSL), Java JDK 8+, Hadoop

## How the System Works
1. **Ingestion:** Transaction data including card ID, amount, merchant ID, and zip code is sent to a Kafka producer.
2. **Streaming:** Spark Streaming subscribes to the Kafka topic and receives data in micro-batches.
3. **Processing:**
   - The **driver** program parses the incoming JSON data.
   - It retrieves the last known location and timestamp for the card ID from the database using **dao.py**.
   - **geo_map.py** calculates the distance and speed required to travel between the last transaction location and the current one.
4. **Rule Application:**
   - **rules.py** evaluates the transaction. If the speed is unrealistic (e.g., > 500 mph) or the amount exceeds a threshold, it is flagged as fraud.
5. **Action:**
   - Fraudulent transactions are logged to a "Fraud" table/collection.
   - Legitimate transactions update the user's history in the database.

## Fraud Detection Rules
The system applies the following logic to flag transactions:
- **Speed Fraud:** If a card is used at Location A and then at Location B, and the travel speed required to reach B from A exceeds a reasonable threshold (e.g., plane speed), it is flagged.
- **Amount Threshold:** Transactions exceeding a user's historical average or specific limit are flagged.
- **Score-based:** A composite score is calculated based on multiple factors; if it crosses a threshold, the transaction is marked suspicious.

## Installation and Setup

### Prerequisites
- Python 3.6+
- Apache Spark 3.x
- Apache Kafka
- HBase or MongoDB running locally or on a server
- Java 8 (required for Spark/Hadoop)

### Environment Setup
1. **Install Python dependencies:**
   ```bash
   pip install pyspark kafka-python happybase
   ```
2. **Start Zookeeper and Kafka:**
   ```bash
   bin/zookeeper-server-start.sh config/zookeeper.properties
   bin/kafka-server-start.sh config/server.properties
   ```
3. **Start Database:**
   - For HBase: `start-hbase.sh`
   - For MongoDB: `mongod`

## How to Run the Project

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/credit-card-fraud-detection.git
   cd credit-card-fraud-detection
   ```

2. **Run the Kafka Producer (simulate transactions):**
   ```bash
   python producer.py
   ```

3. **Submit the Spark Job:**
   ```bash
   spark-submit --packages org.apache.spark:spark-streaming-kafka-0-8_2.11:2.4.8 driver.py
   ```

## Sample Use Case / Example Scenario
**Scenario:**
- **Time 10:00 AM:** User A makes a transaction in **New York** (Zip: 10001).
- **Time 10:30 AM:** User A's card is used for a transaction in **Los Angeles** (Zip: 90001).

**System Reaction:**
- The system calculates the distance (~2,800 miles) and time difference (30 mins).
- Speed calculated = 5,600 mph (Impossible).
- **Result:** Transaction flagged as **FRAUD**.

## Future Enhancements
- **Machine Learning Integration:** Train a Random Forest or Logistic Regression model on historical data to predict fraud probability.
- **Real-time Dashboard:** Build a web interface using Flask/Django or Grafana to visualize fraud alerts live.
- **Scalability:** Deploy on a cloud cluster (AWS EMR or Databricks) for handling millions of transactions per second.

## Conclusion
This project demonstrates the power of Big Data tools in solving real-world security problems. By combining stream processing with rule-based logic, we achieved a low-latency fraud detection system suitable for modern banking needs.

## License
This project is licensed under the MIT License - see the LICENSE file for details.
