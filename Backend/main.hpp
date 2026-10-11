// (c) 2022-present The Riecoin Developers (https://riecoin.xyz/StellaPool/)

#ifndef HEADER_main_hpp
#define HEADER_main_hpp

#include <fstream>
#include "mysql_connection.h"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include "tools.hpp"

using namespace std::string_literals;
using namespace std::chrono_literals;

#define versionString	"StellaPool 2610"

struct Options {
	std::string poolAddress{"ric1pstellap55ue6keg3ta2qwlxr0h58g66fd7y4ea78hzkj3r4lstrsk4clvn"s};
	uint16_t poolPort{2005U};
	double poolFee{0.01};
	uint16_t poolRequiredConfirmations{100U};
	double withdrawalMinimum{0.5}, withdrawalFee{0.01}, withdrawalAutomaticThreshold{5.}, withdrawalProcessingInterval{60.};
	std::string walletHost{"127.0.0.1"s};
	uint16_t walletPort{28332U};
	std::string walletName{""s}, walletUsername{""s}, walletPassword{""s}, walletCookie{""s}, databaseHost{"127.0.0.1"s};
	uint16_t databasePort{3306U};
	std::string databaseName{"Stella"s}, databaseUsername{""s}, databasePassword{""s};
	double databaseUpdateInterval{10.};
};

class Configuration {
	Options _options;
	std::optional<std::pair<std::string, std::string>> _parseLine(const std::string&) const;
public:
	bool parse(const int, char**);
	Options options() const {return _options;}
};

class Database {
	sql::ConnectOptionsMap _connectOptions;
	sql::Driver *_driver;
	bool _ready;
public:
	Database() : _ready(false) {}
	Database(const std::string &host, const uint16_t port, const std::string &name, const std::string &username, const std::string &password) : _ready(false) {
		try {
			_connectOptions["hostName"] = host;
			_connectOptions["userName"] = username;
			_connectOptions["password"] = password;
			_connectOptions["schema"] = name;
			_connectOptions["port"] = port;
			_connectOptions["CLIENT_MULTI_STATEMENTS"] = true;
			_driver = get_driver_instance();
		}
		catch (sql::SQLException &e) {
			SQLERRORMSG("Could not connect to the database");
			return;
		}
		
		try {
			execute("SELECT id, registrationTime, lastLogin, lastSeen, username FROM Users;");
			execute("SELECT address, userId FROM RiecoinAddresses;");
			execute("SELECT tokenHash, userId, expiration FROM Sessions;");
			execute("SELECT id, hash, userId, expiration, powcRemaining, name FROM Tokens;");
			execute("SELECT id, heightStart, heightEnd, timeStart, timeEnd, blockHash, state, finder FROM Rounds;");
			execute("SELECT roundId, miner, points FROM RoundScores;");
			execute("SELECT time, tokenHash, valid, invalid FROM RecentStats;");
			execute("SELECT time, finder, points FROM SharesRecent;");
			execute("SELECT id, time, value, userId, address, comment FROM RiecoinMovements;");
			execute("SELECT id, expiration, amount, userId FROM PoWc;");
			execute("SELECT id, time, value, userId, comment FROM PoWcMovements;");
			execute("SELECT id, time, amount, userId, address, state, txid, comment FROM Withdrawals;");
			execute("SELECT id, time, ip, userId, type, details FROM Events;");
		}
		catch (sql::SQLException &e) {
			SQLERRORMSG("Sanity check failed, invalid database");
			return;
		}
		
		_ready = true;
	};
	
	bool isReady() const {return _ready;}
	
	void execute(const std::string &query) const {
		try {
			sql::ConnectOptionsMap connectOptions(_connectOptions); // For some reason, cannot use _connectOptions directly :|
			std::unique_ptr<sql::Connection> connection(_driver->connect(connectOptions));
			std::unique_ptr<sql::Statement> statement(connection->createStatement());
			statement->execute(query);
		}
		catch (sql::SQLException &e) {
			throw e;
		}
	}
	std::shared_ptr<sql::ResultSet> executeQuery(const std::string &query) const {
		try {
			sql::ConnectOptionsMap connectOptions(_connectOptions);
			std::unique_ptr<sql::Connection> connection(_driver->connect(connectOptions));
			std::unique_ptr<sql::Statement> statement(connection->createStatement());
			std::shared_ptr<sql::ResultSet> result(statement->executeQuery(query));
			return result;
		}
		catch (sql::SQLException &e) {
			throw e;
		}
	}
};

inline Configuration configuration;
inline Database database;

#endif
